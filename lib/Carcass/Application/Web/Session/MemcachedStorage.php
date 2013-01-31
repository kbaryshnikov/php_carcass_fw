<?php

namespace Carcass\Application;

use Carcass\Memcached;
use Carcass\Connection;

class Web_Session_MemcachedStorage implements Web_Session_StorageInterface {

    const
        DEFAULT_MC_KEY = 's_{{ session_id }}',
        DEFAULT_MC_EXPIRE = 3600;

    protected 
        $mc_key_tmpl = self::DEFAULT_MC_KEY,
        $mc_expire = self::DEFAULT_MC_EXPIRE,
        $Memcached;

    public function __construct($memcache_connection_or_dsn) {
        $this->setConnection(
            $memcache_connection_or_dsn instanceof Memcached\Connection
                ? $memcache_connection_or_dsn
                : Connection\Manager::getConnection($memcache_connection_or_dsn)
        );
    }

    public function setConnection(Memcached\Connection $Connection) {
        $this->Memcached = $Connection;
        return $this;
    }

    /**
     * @param string|null $key Memcached key, null = reset to default value
     */
    public function setMcKey($key) {
        $this->mc_key_tmpl = null === $key ? self::DEFAULT_MC_KEY : (string)$key;
        return $this;
    }

    /**
     * @param int|null $expire Memcached key expiration in seconds, null = reset to default value
     * @return void
     */
    public function setMcExpiration($expire = null) {
        $this->mc_expire = null === $expire ? self::DEFAULT_MC_EXPIRE : (int)$expire;
        return $this;
    }

    public function get($session_id) {
        return $this->getDataFromMemcached($session_id);
    }
    
    public function write($session_id, array $data) {
        $this->Memcached->callRequired(
            'set',
            $this->getMcacheKey($session_id),
            $data,
            $this->mc_expire
        );
    }

    public function delete($session_id) {
        $this->Memcached->delete($this->getMcacheKey($session_id));
    }
    
    protected function getMcacheKey($session_id) {
        return $this->Memcached->parseKey($this->mc_key_tmpl, array('session_id' => $session_id));
    }

    protected function getDataFromMemcached($session_id) {
        $key = $this->getMcacheKey($session_id);
        $data = $this->Memcached->get($key);
        return is_array($data) ? $data : [];
    }

}
