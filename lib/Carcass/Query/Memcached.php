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

    public function execute(array $args = []) {
        if (null === $this->key) {
            return parent::execute($args);
        }

        $this->doInTransaction(function($Db, $args) {
            $result = $this->getMct()->getKey($this->key, $args);
            if (false === $result) {
                $result = $this->doFetch($args);
                $this->getMct()->setKey($this->key, $args, $result);
            }
            $this->last_result = $result;
        }, $args);

        return $this;
    }

    protected function doModify(Callable $fn, array $args, $in_transaction, Callable $finally_fn = null) {
        if (null === $this->key) {
            return parent::doModify($fn, $args, $in_transaction, $finally_fn);
        }

        $cache_fn = function($Db, array $args) use ($fn) {
            $affected_rows = $fn($Db, $args);
            if ($affected_rows) {
                $this->getMct()->flush();
            }
            return $affected_rows;
        };
        parent::doModify($cache_fn, $args, true, $finally_fn);
    }

    protected function getMct() {
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
