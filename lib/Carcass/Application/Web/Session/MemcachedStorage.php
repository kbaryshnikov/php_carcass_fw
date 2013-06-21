<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Memcached;
use Carcass\Connection;
use Carcass\Corelib;

/**
 * Stores the session data in memcached.
 * @package Carcass\Application
 */
class Web_Session_MemcachedStorage implements Web_Session_StorageInterface {

    const DEFAULT_MC_KEY = 's_{{ session_id }}';
    const DEFAULT_MC_EXPIRE = 86400;

    const DEFAULT_BIND_MC_KEY = 'sb_{{ uid }}';

    /** @var string */
    protected $mc_key_tmpl = self::DEFAULT_MC_KEY;
    /** @var int */
    protected $mc_expire = self::DEFAULT_MC_EXPIRE;
    /** @var string */
    protected $mc_key_bind = self::DEFAULT_BIND_MC_KEY;
    /** @var \Carcass\Memcached\Connection */
    protected $Memcached;

    /**
     * @param $memcache_connection_or_dsn
     */
    public function __construct($memcache_connection_or_dsn) {
        $this->setConnection(
            $memcache_connection_or_dsn instanceof Memcached\Connection
                ? $memcache_connection_or_dsn
                : DI::getConnectionManager()->getConnection($memcache_connection_or_dsn)
        );
    }

    /**
     * @param \Carcass\Memcached\Connection $Connection
     * @return $this
     */
    public function setConnection(Memcached\Connection $Connection) {
        $this->Memcached = $Connection;
        return $this;
    }

    /**
     * @param string|null $key Memcached key, null = reset to default value
     * @return $this
     */
    public function setMcKey($key) {
        $this->mc_key_tmpl = null === $key ? self::DEFAULT_MC_KEY : (string)$key;
        return $this;
    }

    /**
     * @param int|null $expire Memcached key expiration in seconds, null = reset to default value
     * @return $this
     */
    public function setMcExpiration($expire = null) {
        $this->mc_expire = null === $expire ? self::DEFAULT_MC_EXPIRE : (int)$expire;
        return $this;
    }

    /**
     * @param string $session_id
     * @return array
     */
    public function get($session_id) {
        return $this->getDataFromMemcached($session_id);
    }

    /**
     * @param string $session_id
     * @param array $data
     * @param $is_changed
     * @return $this
     */
    public function write($session_id, array $data, $is_changed) {
        $this->Memcached->callRequired(
            'set',
            $this->getMcacheKey($session_id),
            $data,
            0,
            $this->mc_expire
        );
        return $this;
    }

    /**
     * @param string $session_id
     * @return $this
     */
    public function delete($session_id) {
        $this->Memcached->delete($this->getMcacheKey($session_id));
        return $this;
    }

    /**
     * Returns session id bound to bind_uid
     * @param string $bind_uid
     * @return string|null
     */
    public function getBoundSid($bind_uid) {
        return $this->Memcached->get($this->getBindMcacheKey($bind_uid)) ?: null;
    }

    /**
     * Updates the session id bound to current bind_uid
     *
     * @param string $bind_uid
     * @param string|null $session_id
     * @return $this
     */
    public function setBoundSid($bind_uid, $session_id) {
        if ($session_id) {
            $this->Memcached->set($this->getBindMcacheKey($bind_uid), $session_id);
        } else {
            $this->Memcached->delete($this->getBindMcacheKey($bind_uid));
        }
        return $this;
    }

    /**
     * @param string $session_id
     * @return string
     */
    protected function getMcacheKey($session_id) {
        return Corelib\StringTemplate::constructFromString($this->mc_key_tmpl)->parse(['session_id' => $session_id]);
    }

    /**
     * @param string $bind_uid
     * @return string
     */
    protected function getBindMcacheKey($bind_uid) {
        return Corelib\StringTemplate::constructFromString($this->mc_key_bind)->parse(['uid' => $bind_uid]);
    }

    /**
     * @param $session_id
     * @return array
     */
    protected function getDataFromMemcached($session_id) {
        $key = $this->getMcacheKey($session_id);
        $data = $this->Memcached->get($key);
        return is_array($data) ? $data : [];
    }

}
