<?php

namespace Carcass\Less;

use Carcass\Memcached;
use Carcass\Connection;

class Cacher_Memcache implements Cacher_Interface {

    const DEFAULT_KEY_PREFIX = 'less_';

    protected $MemcachedConnection;
    protected $MemcachedKey;

    public function __construct($memcache_connection_or_dsn, $key_prefix = null) {
        $this->setConnection(
            $memcache_connection_or_dsn instanceof Memcached\Connection
                ? $memcache_connection_or_dsn
                : Connection\Manager::getConnection($dsn)
        );
        $this->setKeyPrefix($key_prefix ?: self::DEFAULT_KEY_PREFIX);
    }

    public function setConnection(Memcached\Connection $Connection) {
        $this->MemcachedConnection = $Connection;
        return $this;
    }

    public function setKeyPrefix($key_prefix = self::DEFAULT_KEY_PREFIX) {
        $this->MemcachedKey = Memcached\Key::create($key_prefix . '_{{ s(less_key) }}');
        return $this;
    }

    public function get($less_key) {
        return $this->MemcachedConnection->get($this->MemcachedKey->__invoke(compact('less_key')));
    }

    public function set($less_key, $value) {
        $this->MemcachedConnection->set($this->MemcachedKey->__invoke(compact('less_key')), $value);
        return $this;
    }

}
