<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Memcached;

use Carcass\Connection\PoolConnectionInterface;
use Carcass\Connection\TransactionalConnectionInterface;
use Carcass\Connection\TransactionalConnectionTrait;
use Carcass\Connection\Dsn;
use Carcass\Connection\DsnPool;
use Carcass\Corelib;
use Carcass\DevTools;

/**
 * Memcached client.
 *
 * Requires pecl/memcache 3.0+.
 *
 * Implements TransactionalConnectionInterface by supporting pseudo-transactions:
 * when a "transaction" is running, write calls are collected to internal queue
 * and executed on commit.
 *
 * @method mixed add()
 * @method mixed cas()
 * @method mixed decrement()
 * @method mixed delete()
 * @method mixed get()
 * @method mixed getStats()
 * @method mixed getVersion()
 * @method mixed increment()
 * @method mixed replace()
 * @method mixed set()
 * @method mixed getServerStatus()
 * @method mixed getExtendedStats()
 * @method mixed flush()
 *
 * @package Carcass\Memcached
 */
class Connection implements PoolConnectionInterface, TransactionalConnectionInterface {
    use TransactionalConnectionTrait;
    use DevTools\TimerTrait;
    use Corelib\UniqueObjectIdTrait {
        Corelib\UniqueObjectIdTrait::getUniqueObjectId as getConnectionId;
    }

    protected static $mc_methods = [
        // name            => delay on transaction
        'add'              => true,
        'cas'              => true,
        'decrement'        => true,
        'delete'           => true,
        'flush'            => false,
        'get'              => false,
        'getStats'         => false,
        'getVersion'       => false,
        'increment'        => true,
        'replace'          => true,
        'set'              => true,
        'getServerStatus'  => false,
        'getExtendedStats' => false,
    ];

    protected static $hit_miss_methods = [
        'get' => true
    ];

    /** @var \Carcass\Connection\DsnPool */
    protected $Pool;
    /** @var \Carcass\Memcached\KeyBuilder|null */
    protected $KeyBuilder = null;
    /** @var \Memcache|null */
    protected $MemcachedInstance = null;
    /** @var bool */
    protected $delay_mode = false;
    /** @var array */
    protected $delayed_calls = [];
    /** @var array */
    protected $last_args = [];

    /**
     * @param \Carcass\Connection\Dsn $Dsn
     * @return static
     */
    public static function constructWithDsn(Dsn $Dsn) {
        return static::constructWithPool(new DsnPool([$Dsn]));
    }

    /**
     * @param \Carcass\Connection\DsnPool $Pool
     * @return static
     */
    public static function constructWithPool(DsnPool $Pool) {
        return new static($Pool);
    }

    /**
     * @return \Carcass\Connection\DsnPool
     */
    public function getDsn() {
        return $this->Pool;
    }

    /**
     * @param \Carcass\Connection\DsnPool $Pool
     */
    public function __construct(DsnPool $Pool) {
        Corelib\Assert::that('DSN type is "memcached"')->is('memcached', $Pool->getType());
        $this->Pool = $Pool;
    }

    /**
     * @param string $template
     * @param array $args
     * @return string
     */
    public function buildKey($template, array $args = []) {
        return $this->getKeyBuilder($template)->parse($args);
    }

    /**
     * Calls the required $method with varargs.
     * If the call fails, LogicException is thrown.
     * Usable with pseudo-transactions to enqueue calls which must cause rollback on failure.
     *
     * @param $method
     * @return mixed
     * @throw \LogicException
     */
    public function callRequired($method /* ... */) {
        $args = func_get_args();
        array_shift($args);
        return $this->dispatch($method, $args, true);
    }

    /**
     * Executes a raw pecl/memcached call.
     * When outside a transaction, identical to normal call.
     * When inside a transaction, bypasses the pseudo-transaction commands queue
     * and executes the memcached $method immediately.
     *
     * @param string $method
     * @return mixed
     */
    public function callRaw($method /* ... */) {
        $args = func_get_args();
        array_shift($args);
        return $this->dispatch($method, $args, false, true);
    }

    /**
     * Executes a raw required (see callRequired()) pecl/memcached call
     *
     * @param string $method
     * @return mixed
     * @throw \LogicException
     */
    public function callRawRequired($method /* ... */) {
        $args = func_get_args();
        array_shift($args);
        return $this->dispatch($method, $args, true, true);
    }

    /**
     * @param $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, array $args) {
        return $this->dispatch($method, $args);
    }

    /**
     * @return void
     */
    public function close() {
        if (null === $this->MemcachedInstance) {
            return;
        }
        $this->MemcachedInstance->close();
        $this->MemcachedInstance = null;
    }

    /**
     * Dispatch delayed pseudo-transaction calls but leave pseudo-transaction status untouched.
     * Useful for "savepoints" emulation
     *
     * @return $this
     * @throws \Exception
     */
    public function dispatchDelayedCalls() {
        $delay_mode = $this->delay_mode;
        $this->delay_mode = false;
        try {
            while ($call = array_shift($this->delayed_calls)) {
                $this->dispatch($call[0], $call[1], $call[2]);
            }
        } catch (\Exception $e) {
            // pass
        }
        // finally:
        $this->delay_mode = $delay_mode;
        if (isset($e)) {
            throw $e;
        }
        return $this;
    }

    protected function beginTransaction() {
        $this->delay_mode = true;
        $this->delayed_calls = [];
    }

    protected function commitTransaction() {
        $this->delay_mode = false;
        $this->dispatchDelayedCalls();
    }

    protected function rollbackTransaction() {
        $this->delay_mode = false;
        $this->delayed_calls = [];
    }

    /**
     * @param $method
     * @param array $args
     * @param bool $is_required
     * @param bool $no_delay
     * @return mixed
     * @throws \LogicException
     * @throws \BadMethodCallException
     */
    protected function dispatch($method, array $args, $is_required = false, $no_delay = false) {
        if (!isset(static::$mc_methods[$method])) {
            throw new \BadMethodCallException("Invalid method call: '$method'");
        }

        if (!$no_delay) {
            $this->triggerScheduledTransaction();
        }

        if (!$no_delay && $this->delay_mode && true == static::$mc_methods[$method]) {
            $delayed_call = [$method, $args, $is_required];
            $this->delayed_calls = array_filter(
                $this->delayed_calls, function ($value) use ($delayed_call) {
                    return $value != $delayed_call;
                }
            );
            $this->delayed_calls[] = $delayed_call;
            return true;
        }

        $MemcachedInstance = $this->getMemcachedInstance();

        $get_hit_miss = isset(static::$hit_miss_methods[$method]) && !empty($args[0]);

        $result = $this->develCollectExecutionTime(
            function () use ($method, $args) {
                return $method . (isset($args[0]) ? ' ' . json_encode($args[0]) : '');
            },
            function () use ($MemcachedInstance, $method, $args) {
                $result = call_user_func_array([$this->getMemcachedInstance(), $method], $args);
                $this->last_args = $args;
                return $result;
            },
            $get_hit_miss ? function ($result) use ($method, $args) {
                $message = [];
                if (!is_array($args[0])) {
                    $message[] = $args[0] . ':' . ($result === false ? 'MISS' : 'HIT');
                } else {
                    foreach ($args[0] as $key) {
                        $message[] = $key . ':' . ((is_array($result) && isset($result[$key]) && false !== $result[$key]) ? 'HIT' : 'MISS');
                    }
                }
                return ' ' . join(' ', $message);
            } : null
        );

        if (false === $result && $is_required) {
            throw new \LogicException("Required call {$method}() returned false");
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getLastArgs() {
        return $this->last_args;
    }

    /**
     * @return \Memcache
     */
    protected function getMemcachedInstance() {
        if (null === $this->MemcachedInstance) {
            $this->develCollectExecutionTime(
                function () {
                    return 'connect: ' . $this->Pool;
                },
                function () {
                    $this->MemcachedInstance = $this->assembleMemcachedInstance();
                }
            );
        }
        return $this->MemcachedInstance;
    }

    /**
     * @param $template
     * @return KeyBuilder
     */
    protected function getKeyBuilder($template) {
        if (null === $this->KeyBuilder) {
            $this->KeyBuilder = $this->assembleKeyBuilder();
        } else {
            $this->KeyBuilder->cleanAll();
        }
        $this->KeyBuilder->load($template);
        return $this->KeyBuilder;
    }

    /**
     * @return KeyBuilder
     */
    protected function assembleKeyBuilder() {
        return new KeyBuilder;
    }

    /**
     * @param KeyBuilder $KeyBuilder
     * @return $this
     */
    public function setKeyBuilder(KeyBuilder $KeyBuilder) {
        $this->KeyBuilder = $KeyBuilder;
        return $this;
    }

    /**
     * @param $Mc
     * @return $this
     */
    public function setMemcacheInstance($Mc) {
        $this->MemcachedInstance = $Mc;
        return $this;
    }

    /**
     * @return \Memcache
     */
    protected function constructMemcacheInstance() {
        return new \Memcache;
    }

    /**
     * @return \Memcache
     */
    protected function assembleMemcachedInstance() {
        $Mc = $this->constructMemcacheInstance();

        $compress_threshold = 0;
        $compress_threshold_min_savings = -1;

        foreach ($this->Pool as $Item) {
            /** @var Corelib\DatasourceInterface $Item */
            $Mc->addServer(
                $Item->has('socket') ? ('unix://' . $Item->get('socket')) : $Item->get('hostname'),
                $Item->has('socket') ? 0 : ($Item->get('port') ? : 11211),
                $Item->get('args')->get('persistent', true),
                $Item->get('args')->get('weight', 1),
                $Item->get('args')->get('timeout', 1),
                $Item->get('args')->get('retry_interval', 10),
                $Item->get('args')->get('status', true)
            );
            if ($args_threshold = $Item->get('args')->get('compress_threshold')) {
                $compress_threshold = max($compress_threshold, intval($args_threshold));
            }
            if ($args_savings = $Item->get('args')->get('compress_threshold_min_savings')) {
                $compress_threshold_min_savings = max($compress_threshold_min_savings, floatval($args_savings));
            }
        }
        if ($compress_threshold > 0) {
            if ($compress_threshold_min_savings >= 0) {
                $Mc->setCompressThreshold($compress_threshold, $compress_threshold_min_savings);
            } else {
                $Mc->setCompressThreshold($compress_threshold);
            }
        }
        return $Mc;
    }

    protected function develGetTimerGroup() {
        return 'memcached';
    }

    protected function develGetTimerMessage($message) {
        return sprintf('[%s] %s', $this->getConnectionId(), $message);
    }
}
