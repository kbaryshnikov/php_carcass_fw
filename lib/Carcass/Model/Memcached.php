<?php

namespace Carcass\Model;

use Carcass\Query;

class Memcached extends Base {

    protected static
        $cache_key = null, // null: undefined, false: no cache, or key template
        $cache_tags = [];

    protected
        $override_cache_key = null,
        $override_cache_tags = null;

    protected function setNoCache() {
        return $this->setCacheKeys(false);
    }

    protected function setCacheKeys($override_cache_key = null, array $override_cache_tags = null) {
        $this->override_cache_key = $override_cache_key;
        $this->override_cache_tags = $override_cache_tags;
        $this->Query = null;
        return $this;
    }

    protected function assembleQuery() {
        return $this->createQueryInstance()->setTags($this->getCacheTags())->useCache($this->getCacheKey() ?: null);
    }

    protected function createQueryInstance() {
        return new Query\Memcached;
    }

    protected function getCacheKey() {
        if (null !== $this->override_cache_key) {
            $result = $this->override_cache_key;
        } else {
            if (null === static::$cache_key) {
                throw new \LogicException('Model cache key is undefined');
            }
            $result = static::$cache_key;
        }
        return $result;
    }

    protected function getCacheTags() {
        if (is_array($this->override_cache_tags)) {
            return $this->override_cache_tags;
        }
        return static::$cache_tags;
    }

    protected function doInsert($query, array $args = [], $id_key = null) {
        if (!$this->validate()) {
            return false;
        }
        return $this->getQuery()->insert($query, $args + $this->exportArray(), $id_key);
    }

}
