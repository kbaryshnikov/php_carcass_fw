<?php

namespace Carcass\Query;

use Carcass\Corelib;
use Carcass\Memcached\TaggedCache;
use Carcass\Memcached\Key as MemcachedKey;
use Carcass\Application\Injector;

class Memcached extends Base {

    protected
        $key = null,
        $tags = [],
        $MemcachedConnection = null,
        $last_insert_id_field_name = null,
        $TaggedCache = null;

    public function setTags(array $tags) {
        $this->tags = $tags;
        if (null !== $this->TaggedCache) {
            $this->TaggedCache->setTags($this->tags);
        }
        return $this;
    }

    public function useCache($key) {
        $this->key = $key ? $this->assembleCacheKey($key) : null;
        return $this;
    }

    protected function assembleCacheKey($key) {
        return MemcachedKey::create($key);
    }

    public function insert($sql_query_template, array $args = array(), $last_insert_id_field_name = false) {
        $this->last_insert_id_field_name = $last_insert_id_field_name ?: false;
        return parent::insert($sql_query_template, $args);
    }

    public function modify($sql_query_template, array $args = array()) {
        $this->last_insert_id_field_name = null;
        return parent::modify($sql_query_template, $args);
    }

    public function insertWith(Callable $fn, array $args, $last_insert_id_field_name = false, $in_transaction = true, Callable $finally_fn = null) {
        $this->last_insert_id_field_name = $last_insert_id_field_name ?: false;
        return parent::insertWith($fn, $args, $in_transaction, $finally_fn);
    }

    public function modifyWith(Callable $fn, array $args, $in_transaction = true, Callable $finally_fn = null) {
        $this->last_insert_id_field_name = null;
        return parent::modifyWith($fn, $args, $in_transaction, $finally_fn);
    }

    public function execute(array $args = []) {
        if (null === $this->key) {
            return parent::execute($args);
        }

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

    public function getMct() {
        if (null === $this->TaggedCache) {
            $this->TaggedCache = $this->assembleMct();
        }
        return $this->TaggedCache;
    }

    protected function assembleMct() {
        return new TaggedCache($this->getMemcachedConnection(), $this->tags);
    }

    protected function getMemcachedConnection() {
        if (null === $this->MemcachedConnection) {
            $this->MemcachedConnection = $this->assembleMemcachedConnection();
        }
        return $this->MemcachedConnection;
    }

    protected function assembleMemcachedConnection() {
        return Injector::getConnectionManager()->getConnection($this->getMemcachedDsn());
    }

    protected function getMemcachedDsn() {
        return Injector::getConfigReader()->getPath('application.connections.memcached');
    }

}
