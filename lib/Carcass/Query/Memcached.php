<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Query;

use Carcass\Corelib;
use Carcass\Memcached\TaggedCache;
use Carcass\Memcached\Key as MemcachedKey;
use Carcass\Application\DI;

/**
 * Memcached query, with tags support
 * @package Carcass\Query
 */
class Memcached extends Base {

    protected
        $key = null,
        $tags = [],
        $key_options = [],
        $mc_dsn = null,
        $last_insert_id_field_name = null;

    /**
     * @var \Carcass\Memcached\Connection
     */
    protected $MemcachedConnection = null;
    /**
     * @var TaggedCache
     */
    protected $TaggedCache = null;

    /**
     * @param array $key_options
     * @return $this
     */
    public function setKeyOptions(array $key_options = []) {
        $this->key_options = $key_options;
        return $this;
    }

    /**
     * @param array $tags
     * @return $this
     */
    public function setTags(array $tags) {
        $this->tags = $tags;
        if (null !== $this->TaggedCache) {
            $this->TaggedCache->setTags($this->tags);
        }
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function useCache($key) {
        $this->key = $key ? $this->assembleCacheKey($key) : null;
        return $this;
    }

    /**
     * @param $key
     * @return callable
     */
    protected function assembleCacheKey($key) {
        return $key instanceof \Closure ? $key : MemcachedKey::create($key);
    }

    /**
     * @param string $sql_query_template
     * @param array $args
     * @param bool $last_insert_id_field_name
     * @return null
     */
    public function insert($sql_query_template, array $args = array(), $last_insert_id_field_name = false) {
        $this->last_insert_id_field_name = $last_insert_id_field_name ?: false;
        return parent::insert($sql_query_template, $args);
    }

    /**
     * @param string $sql_query_template
     * @param array $args
     * @return mixed
     */
    public function modify($sql_query_template, array $args = array()) {
        $this->last_insert_id_field_name = null;
        return parent::modify($sql_query_template, $args);
    }

    /**
     * @param callable $fn
     * @param array $args
     * @param bool $last_insert_id_field_name
     * @param bool $in_transaction
     * @param callable $finally_fn
     * @return mixed
     */
    public function insertWith(Callable $fn, array $args, $last_insert_id_field_name = false, $in_transaction = true, Callable $finally_fn = null) {
        $this->last_insert_id_field_name = $last_insert_id_field_name ?: false;
        return parent::insertWith($fn, $args, $in_transaction, $finally_fn);
    }

    /**
     * @param callable $fn
     * @param array $args
     * @param bool $in_transaction
     * @param callable $finally_fn
     * @return mixed
     */
    public function modifyWith(Callable $fn, array $args, $in_transaction = true, Callable $finally_fn = null) {
        $this->last_insert_id_field_name = null;
        return parent::modifyWith($fn, $args, $in_transaction, $finally_fn);
    }

    /**
     * @param array $args
     * @return $this
     */
    public function execute(array $args = []) {
        if (null === $this->key) {
            return parent::execute($args);
        }

        /** @noinspection PhpUnusedParameterInspection */
        $this->doInTransaction(function($Db, $args) {
            $result = $this->getMct()->getKey($this->key, $args);
            if (false === $result) {
                $result = $this->doFetch($args);
                $this->getMct()->setKey($this->key, $result, $args);
            }
            $this->last_result = $result;
        }, $args);

        return $this;
    }

    /**
     * @param callable $fn
     * @param array $args
     * @param $in_transaction
     * @param callable $finally_fn
     * @return mixed
     */
    protected function doModify(Callable $fn, array $args, $in_transaction, Callable $finally_fn = null) {
        return null === $this->key
            ? parent::doModify($fn, $args, $in_transaction, $finally_fn)
            : parent::doModify(function($Db, array $args) use ($fn) {
                $affected_rows = $fn($Db, $args);
                if ($affected_rows) {
                    if ($this->last_insert_id_field_name !== null) {
                        if ($this->last_insert_id_field_name !== false && $this->last_insert_id) {
                            $args[$this->last_insert_id_field_name] = $this->last_insert_id;
                            $this->getMct()->flush($args, [$this->key]);
                        }
                    } else {
                        $this->getMct()->flush($args);
                    }
                }
                return $affected_rows;
            }, $args, true, $finally_fn);
    }

    /**
     * @return \Carcass\Memcached\TaggedCache
     */
    public function getMct() {
        if (null === $this->TaggedCache) {
            $this->TaggedCache = $this->assembleMct();
        }
        return $this->TaggedCache;
    }

    /**
     * @param array $options
     * @return \Carcass\Memcached\TaggedCache
     */
    protected function assembleMct(array $options = []) {
        return new TaggedCache($this->getMemcachedConnection(), $this->tags, $options);
    }

    /**
     * @return \Carcass\Connection\ConnectionInterface|\Carcass\Memcached\Connection|null
     */
    protected function getMemcachedConnection() {
        if (null === $this->MemcachedConnection) {
            $this->MemcachedConnection = $this->assembleMemcachedConnection();
        }
        return $this->MemcachedConnection;
    }

    /**
     * @return \Carcass\Connection\ConnectionInterface
     */
    protected function assembleMemcachedConnection() {
        return DI::getConnectionManager()->getConnection($this->getMemcachedDsn());
    }

    /**
     * @return \Carcass\Config\ItemInterface|null|string
     */
    protected function getMemcachedDsn() {
        return $this->mc_dsn ?: DI::getConfigReader()->getPath('application.connections.memcached');
    }

    /**
     * @param $mc_dsn
     * @return $this
     */
    public function setMemcachedDsn($mc_dsn) {
        $this->mc_dsn = $mc_dsn;
        return $this;
    }

}
