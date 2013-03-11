<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Less;

use Carcass\Memcached;
use Carcass\Connection;
use Carcass\Application\DI;

/**
 * Memcached LESS cacher
 * @package Carcass\Less
 */
class Cacher_Memcached implements Cacher_Interface {

    const DEFAULT_KEY_PREFIX = 'less_';

    /**
     * @var Memcached\Connection
     */
    protected $MemcachedConnection;
    /**
     * @var \Closure
     */
    protected $MemcachedKey;

    /**
     * @param Memcached\Connection|Connection\DsnInterface|array|string $memcache_connection_or_dsn
     * @param null $key_prefix
     */
    public function __construct($memcache_connection_or_dsn, $key_prefix = null) {
        $this->setConnection(
            $memcache_connection_or_dsn instanceof Memcached\Connection
                ? $memcache_connection_or_dsn
                : DI::getConnectionManager()->getConnection($memcache_connection_or_dsn)
        );
        $this->setKeyPrefix($key_prefix ?: self::DEFAULT_KEY_PREFIX);
    }

    /**
     * @param \Carcass\Memcached\Connection $Connection
     * @return $this
     */
    public function setConnection(Memcached\Connection $Connection) {
        $this->MemcachedConnection = $Connection;
        return $this;
    }

    /**
     * @param string $key_prefix
     * @return $this
     */
    public function setKeyPrefix($key_prefix = self::DEFAULT_KEY_PREFIX) {
        $this->MemcachedKey = Memcached\Key::create($key_prefix . '_{{ s(less_key) }}');
        return $this;
    }

    /**
     * @param string $less_key
     * @return mixed
     */
    public function get($less_key) {
        $Key = $this->MemcachedKey;
        return $this->MemcachedConnection->get($Key(compact('less_key')));
    }

    /**
     * @param string $less_key
     * @param string $value
     * @return $this
     */
    public function put($less_key, $value) {
        $Key = $this->MemcachedKey;
        $this->MemcachedConnection->set($Key(compact('less_key')), $value);
        return $this;
    }

}
