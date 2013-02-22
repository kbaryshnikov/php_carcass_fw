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

/**
 * Memcached connection and client.
 * Uses pecl/memcache.
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
 *
 * @package Carcass\Memcached
 */
class Connection implements PoolConnectionInterface, TransactionalConnectionInterface {
    use TransactionalConnectionTrait;

    protected static $mc_calls = [
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
     * Calls the required $method with varargs. If a method fails, LogicException is thrown.
     * @param $method
     * @return bool|mixed
     * @throw \LogicException
     */
    public function callRequired(/** @noinspection PhpUnusedParameterInspection */ $method /* ... */) {
        $args = func_get_args();
        return $this->dispatch(array_shift($args), $args, true);
    }

    /**
     * Executes a raw pecl/memcached call
     *
     * @param string $method
     * @return mixed
     */
    public function callRaw(/** @noinspection PhpUnusedParameterInspection */ $method /* ... */) {
        $args = func_get_args();
        return $this->dispatch(array_shift($args), $args, false, true);
    }

    /**
     * Executes a raw required (see callRequired()) pecl/memcached call
     *
     * @param string $method
     * @return mixed
     * @throw \LogicException
     */
    public function callRawRequired(/** @noinspection PhpUnusedParameterInspection */ $method /* ... */) {
        $args = func_get_args();
        return $this->dispatch(array_shift($args), $args, true, true);
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

    protected function beginTransaction() {
        $this->delay_mode    = true;
        $this->delayed_calls = [];
    }

    protected function commitTransaction() {
        $this->delay_mode = false;
        while ($call = array_shift($this->delayed_calls)) {
            $this->dispatch($call[0], $call[1], $call[2]);
        }
    }

    protected function rollbackTransaction() {
        $this->delay_mode    = false;
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
        if (!isset(static::$mc_calls[$method])) {
            throw new \BadMethodCallException("Invalid method call: '$method'");
        }
        if (!$no_delay) {
            $this->triggerScheduledTransaction();
        }
        if (!$no_delay && $this->delay_mode && true == static::$mc_calls[$method]) {
            $this->delayed_calls[] = [$method, $args, $is_required];
            $result                = true;
        } else {
            $result = call_user_func_array([$this->getMc(), $method], $args);
            if (false === $result && $is_required) {
                throw new \LogicException("Required call {$method}() returned false");
            }
        }
        return $result;
    }

    /**
     * @return \Memcache
     */
    protected function getMc() {
        if (null === $this->MemcachedInstance) {
            $this->MemcachedInstance = $this->assembleMemcachedInstance();
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

        $compress_threshold             = 0;
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

}
