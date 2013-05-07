<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Model;

use Carcass\Query;

/**
 * Memcached model trait
 *
 * @package Carcass\Model
 */
trait MemcachedQueryTrait {

    /**
     * @var string|bool|null  if not null, overrides static::$cache_key
     */
    protected $override_cache_key = null;
    /**
     * @var array|null  if not null, overrides static::$cache_tags
     */
    protected $override_cache_tags = null;

    /**
     * @return null|string|bool  null: undefined, false: no cache, or key template
     */
    protected static function getCacheKey() {
        if (property_exists(get_called_class(), 'cache_key')) {
            /** @noinspection PhpUndefinedFieldInspection */
            return static::$cache_key;
        }
        return null;
    }

    protected static function getCacheTags() {
        if (property_exists(get_called_class(), 'cache_tags')) {
            /** @noinspection PhpUndefinedFieldInspection */
            return static::$cache_tags;
        }
        return [];
    }

    /**
     * @return $this
     */
    protected function setNoCache() {
        return $this->setCacheKey(false);
    }

    /**
     * @param string|null $override_cache_key
     * @param array|null $override_cache_tags
     * @return $this
     */
    protected function setCacheKey($override_cache_key = null, array $override_cache_tags = null) {
        $this->override_cache_key  = $override_cache_key;
        $this->override_cache_tags = $override_cache_tags;

        if (isset($this->QueryDispatcher)) {
            $this->QueryDispatcher = null;
        }
        return $this;
    }

    /**
     * @param int|null $chunk_size, int chunk size, or null for non-chunked mode
     * @throws \LogicException
     * @return \Carcass\Query\MemcachedDispatcher
     */
    protected function getListQueryDispatcher($chunk_size = null) {
        /** @noinspection PhpUndefinedMethodInspection */
        $QueryDispatcher = $this->getQueryDispatcher();
        if (!$QueryDispatcher instanceof Query\MemcachedDispatcher) {
            throw new \LogicException("instanceof Query\\MemcachedDispatcher expected");
        }
        return $QueryDispatcher->setListChunkSize($chunk_size);
    }

    protected function configureMemcachedQueryDispatcher(Query\MemcachedDispatcher $QueryDispatcher) {
        if (null !== $config_path = $this->getConfigMemcachedDsnPath()) {
            $QueryDispatcher->setConfigDatabaseDsnPath($config_path);
        }
        return $QueryDispatcher
            ->setTags($this->getCurrentCacheTags())
            ->useCache($this->getCurrentCacheKey());
    }

    /**
     * @return string|null
     */
    protected function getConfigMemcachedDsnPath() {
        return null;
    }

    /**
     * @return string
     * @throws \LogicException
     */
    protected function getCurrentCacheKey() {
        if (null !== $this->override_cache_key) {
            $result = $this->override_cache_key;
        } else {
            if (null === static::getCacheKey()) {
                throw new \LogicException('Model cache key is undefined');
            }
            $result = static::getCacheKey();
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function getCurrentCacheTags() {
        if (is_array($this->override_cache_tags)) {
            return $this->override_cache_tags;
        }
        return static::getCacheTags();
    }

}