<?php

namespace Carcass\Memcached;

use Carcass\Connection\ConnectionInterface;
use Carcass\Connection\PoolConnectionInterface;
use Carcass\Connection\TransactionalConnectionInterface;
use Carcass\Connection\TransactionalConnectionTrait;
use Carcass\Connection\Dsn;
use Carcass\Connection\DsnPool;
use Carcass\Corelib;

class Connection implements PoolConnectionInterface, TransactionalConnectionInterface {
    use TransactionalConnectionTrait;

    protected static
        $mc_calls = [
            // name => delay on transaction
            'add'       => true,
            'decrement' => true,
            'delete'    => true,
            'flush'     => false,
            'get'       => false,
            'getStats'  => false,
            'getVersion'=> false,
            'increment' => true,
            'replace'   => true,
            'set'       => true,
            'getServerStatus' => false,
            'getExtendedStats' => false,
        ];

    protected
        $Pool,
        $KeyBuilder = null,
        $MemcachedInstance = null,
        $delay_mode = false,
        $delayed_calls = [];

    public static function constructWithDsn(Dsn $Dsn) {
        return static::constructWithPool(new DsnPool([$Dsn]));
    }

    public static function constructWithPool(DsnPool $Pool) {
        return new static($Pool);
    }

    public function getDsn() {
        return $this->Pool;
    }

    public function __construct(DsnPool $Pool) {
        Corelib\Assert::onFailureThrow('memcached dsn is required')->is('memcached', $Pool->getType());
        $this->Pool = $Pool;
    }

    public function buildKey($template, array $args = []) {
        return $this->getKeyBuilder($template)->parse($args);
    }

    public function callRequired($method /* ... */) {
        $args = func_get_args();
        return $this->dispatch(array_shift($args), $args, true);
    }

    public function callRaw($method, $args) {
        $args = func_get_args();
        return $this->dispatch(array_shift($args), $args, false, true);
    }

    public function callRawRequired($method, $args) {
        $args = func_get_args();
        return $this->dispatch(array_shift($args), $args, true, true);
    }

    public function __call($method, array $args) {
        return $this->dispatch($method, $args);
    }

    public function close() {
        if (null === $this->MemcachedInstance) {
            return true;
        }
        $result = $this->MemcachedInstance->close();
        $this->MemcachedInstance = null;
        return $result;
    }

    protected function beginTransaction() {
        $this->delay_mode = true;
        $this->delayed_calls = [];
    }

    protected function commitTransaction() {
        $this->delay_mode = false;
        while ($call = array_shift($this->delayed_calls)) {
            $this->dispatch($call[0], $call[1], $call[2]);
        }
    }

    protected function rollbackTransaction() {
        $this->delay_mode = false;
        $this->delayed_calls = [];
    }

    protected function dispatch($method, array $args, $is_required = false, $no_delay = false) {
        if (!isset(static::$mc_calls[$method])) {
            throw new \BadMethodCallException("Invalid method call: '$method'");
        }
        if (!$no_delay) {
            $this->triggerScheduledTransaction();
        }
        if (!$no_delay && $this->delay_mode && true == static::$mc_calls[$method]) {
            $this->delayed_calls[] = [$method, $args, $is_required];
            $result = true;
        } else {
            $result = call_user_func_array([$this->getMc(), $method], $args);
            if (false === $result && $is_required) {
                throw new \LogicException("Required call {$method}() returned false");
            }
        }
        return $result;
    }

    protected function getMc() {
        if (null === $this->MemcachedInstance) {
            $this->MemcachedInstance = $this->assembleMemcachedInstance();
        }
        return $this->MemcachedInstance;
    }

    protected function getKeyBuilder($template) {
        if (null === $this->KeyBuilder) {
            $this->KeyBuilder = $this->assembleKeyBuilder();
        } else {
            $this->KeyBuilder->cleanAll();
        }
        $this->KeyBuilder->load($template);
        return $this->KeyBuilder;
    }

    protected function assembleKeyBuilder() {
        return new KeyBuilder;
    }

    public function setKeyBuilder(KeyBuilder $KeyBuilder) {
        $this->KeyBuilder = $KeyBuilder;
        return $this;
    }

    public function setMemcacheInstance($Mc) {
        $this->MemcachedInstance = $Mc;
        return $this;
    }

    protected function constructMemcacheInstance() {
        return new \Memcache;
    }

    protected function assembleMemcachedInstance() {
        $Mc = $this->constructMemcacheInstance();
        $compress_threshold = 0;
        $compress_threshold_min_savings = -1;
        foreach ($this->Pool as $Item) {
            $Mc->addServer(
                $Item->has('socket') ? ('unix://' . $Item->socket) : $Item->hostname,
                $Item->has('socket') ? 0 : ($Item->get('port') ?: 11211),
                $Item->args->get('persistent', true),
                $Item->args->get('weight', 1),
                $Item->args->get('timeout', 1),
                $Item->args->get('retry_interval', 10),
                $Item->args->get('status', true)
            );
            if ($Item->args->get('compress_threshold')) {
                $compress_threshold = max($compress_threshold, intval($Item->args->compress_threshold));
            }
            if ($Item->args->get('compress_threshold_min_savings')) {
                $compress_threshold_min_savings = max($compress_threshold_min_savings, floatval($Item->args->compress_threshold_min_savings));
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
