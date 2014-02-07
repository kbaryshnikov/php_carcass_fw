<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Query;

use Carcass\Corelib;
use Carcass\Mysql;
use Carcass\Memcached\TaggedCache;
use Carcass\Memcached\Key as MemcachedKey;
use Carcass\Application\DI;

/**
 * Memcached query dispatcher, with tags support
 *
 * @package Carcass\Query
 */
class MemcachedDispatcher extends BaseDispatcher {

    /** @var callable|null */
    protected $key = null;

    protected
        $tags = [],
        $key_options = [],
        $chunk_size = null,
        $mc_dsn = null,
        $is_own_fetch_fn = false,
        $last_insert_id_field_name = null,
        $config_memcached_dsn_path = null;

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
     * @param $sql_query_template
     * @param array $keys
     * @param string $count_modifier
     * @return $this
     */
    public function fetchList($sql_query_template, array $keys = [], $count_modifier = self::DEFAULT_COUNT_MODIFIER) {
        $this->is_own_fetch_fn = true;
        return parent::fetchList($sql_query_template, $keys, $count_modifier);
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
     * @return mixed
     */
    public function modify($sql_query_template, array $args = array()) {
        $this->last_insert_id_field_name = null;
        return parent::modify($sql_query_template, $args);
    }

    /**
     * @param string $field_name
     * @return $this
     */
    public function setLastInsertIdFieldName($field_name) {
        $this->last_insert_id_field_name = $field_name ? (string)$field_name : false;
        return $this;
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
    protected function doFetch(array $args) {
        $is_own_fetch_fn = false;
        if ($this->is_own_fetch_fn) {
            $is_own_fetch_fn       = true;
            $this->is_own_fetch_fn = false;
        }

        if (!$this->hasCacheKey() || $is_own_fetch_fn) {
            return parent::doFetch($args);
        }

        /** @noinspection PhpUnusedParameterInspection */
        $this->doInTransaction(
            function (Mysql\Client $DbUnused, $args) {
                $result = $this->getMct()->getKey($this->key, $args);
                if (false === $result) {
                    $result = parent::doFetch($args);
                    $this->getMct()->setKey($this->key, $result, $args);
                }
                $this->last_result = $result;
            },
            $args
        );

        return $this->last_result;
    }

    protected function isCacheEnabled() {
        return $this->hasCacheKey() || !empty($this->tags);
    }

    protected function hasCacheKey() {
        return null !== $this->key;
    }

    /**
     * @param callable $fn
     * @param array $args
     * @param $in_transaction
     * @param callable $finally_fn
     * @return mixed
     */
    protected function doModify(Callable $fn, array $args, $in_transaction, Callable $finally_fn = null) {
        if (!$this->isCacheEnabled()) {
            return parent::doModify($fn, $args, $in_transaction, $finally_fn);
        }

        return parent::doModify(
            function ($Db, array $args) use ($fn) {
                $affected_rows = $fn($Db, $args);
                if ($affected_rows) {
                    if ($this->last_insert_id_field_name !== null) {
                        if ($this->last_insert_id_field_name !== false && $this->last_insert_id) {
                            $args[$this->last_insert_id_field_name] = $this->last_insert_id;
                        }
                    }
                    $this->flushMemcachedTags($args);
                }
                return $affected_rows;
            }, $args, true, $finally_fn
        );
    }

    public function flushMemcachedTags(array $args) {
        if ($this->hasCacheKey()) {
            $this->getMct()->flush($args, [$this->key]);
        } else {
            $this->getMct()->flush($args);
        }
        return $this;
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
        return $this->mc_dsn ? : DI::getConfigReader()->getPath($this->getConfigMemcachedDsnPath());
    }

    /**
     * @return string
     */
    protected function getConfigMemcachedDsnPath() {
        return $this->config_memcached_dsn_path ? : 'application.connections.memcached';
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setConfigMemcachedDsnPath($path) {
        $this->config_memcached_dsn_path = $path;
        return $this;
    }

    /**
     * @param $mc_dsn
     * @return $this
     */
    public function setMemcachedDsn($mc_dsn) {
        $this->mc_dsn = $mc_dsn;
        return $this;
    }

    /**
     * @param int|null $chunk_size
     * @return $this
     */
    public function setListChunkSize($chunk_size = null) {
        $this->chunk_size = $chunk_size ? intval($chunk_size) : null;
        return $this;
    }

    /**
     * @param int|null $count
     * @return $this
     */
    public function setListCount($count = null) {
        $this->last_count = $count ? intval($count) : null;
        return $this;
    }

    protected function doFetchList($sql_query_template, array $args, array $keys = [], $count_modifier = self::DEFAULT_COUNT_MODIFIER) {
        if (null === $this->key) {
            return parent::doFetchList($sql_query_template, $args, $keys, $count_modifier);
        }

        if ($this->chunk_size) {
            return $this->doFetchChunkedList($sql_query_template, $args, $keys, $count_modifier);
        } else {
            return $this->doFetchEntireList($sql_query_template, $args, $keys, $count_modifier);
        }
    }

    protected function doFetchEntireList($sql_query_template, array $args, array $keys = [], $count_modifier = self::DEFAULT_COUNT_MODIFIER) {
        $cache_args = $this->mixListArgsInto($args);
        $cached_result = $this->getMct()->getKey($this->key, $cache_args);
        if ($cached_result && is_array($cached_result) && isset($cached_result['d']) && isset($cached_result['c'])) {
            $result = $cached_result['d'];
            $this->last_count = $cached_result['c'];
        } else {
            $result = parent::doFetchList($sql_query_template, $args, $keys, $count_modifier);
            $this->getMct()->setKey($this->key, ['d' => $result, 'c' => $this->last_count], $cache_args);
        }
        return $result;
    }

    protected function doFetchChunkedList($sql_query_template, array $args, array $keys = [], $count_modifier = self::DEFAULT_COUNT_MODIFIER) {
        if (!$this->limit) {
            throw new \LogicException('Chunked list fetch mode enabled, but setLimit() was not called or defined no limit');
        }

        $key = $this->key;

        $ListCache = $this->getMct()->getListCache($key('getTemplate'), $this->chunk_size);

        $result = $ListCache->get($args, $this->offset, $this->limit);

        if (false !== $result) {
            $this->last_count = $ListCache->getCount();
        } else {
            $result = parent::doFetchList($sql_query_template, $args, $keys, $count_modifier);
            $ListCache->setCount($this->last_count);
            $ListCache->set($args, array_values($result), intval($this->offset));
        }

        return $result;
    }

}
