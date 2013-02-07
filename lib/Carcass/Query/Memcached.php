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

        $this->doInTransaction(function() use($args) {
            $key = $this->key->__invoke($args);
            $result = $this->getMct()->get($key);
            if (false === $cache_result) {
                $result = parent::execute($args);
                $this->getMct()->set($key, $result);
            }
            $this->last_result = $result;
        });

        return $this;
    }

    public function modify($sql_query_template, array $args = array()) {
        return $this->doInTransaction(function() use ($sql_query_template, $args) {
            $affected_rows = parent::modify($sql_query_template, $args);
            if ($affected_rows) {
                $this->getMct()->flush();
            }
            return $affected_rows;
        });
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
