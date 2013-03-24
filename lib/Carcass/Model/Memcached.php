<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
namespace Carcass\Model;

use Carcass\Query;

/**
 * Memcached Model
 *
 * @method \Carcass\Query\Memcached getQuery()
 *
 * @package Carcass\Model
 */
abstract class Memcached extends Base {

    /**
     * @var string|bool|null  null: undefined, false: no cache, or key template
     */
    protected static $cache_key = null;
    /**
     * @var array
     */
    protected static $cache_tags = [];

    /**
     * @var string ID key for insert queries
     */
    protected $id_key = null;

    /**
     * @var string|bool|null  if not null, overrides static::$cache_key
     */
    protected $override_cache_key = null;
    /**
     * @var array|null  if not null, overrides static::$cache_tags
     */
    protected $override_cache_tags = null;

    /**
     * @return $this
     */
    protected function setNoCache() {
        return $this->setCacheKeys(false);
    }

    /**
     * @param string|null $override_cache_key
     * @param array|null $override_cache_tags
     * @return $this
     */
    protected function setCacheKeys($override_cache_key = null, array $override_cache_tags = null) {
        $this->override_cache_key = $override_cache_key;
        $this->override_cache_tags = $override_cache_tags;
        $this->Query = null;
        return $this;
    }

    /**
     * @return $this
     */
    protected function assembleQuery() {
        return $this->createQueryInstance()->setTags($this->getCacheTags())->useCache($this->getCacheKey() ?: null);
    }

    /**
     * @return \Carcass\Query\Memcached
     */
    protected function createQueryInstance() {
        return new Query\Memcached;
    }

    /**
     * @return string
     * @throws \LogicException
     */
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

    /**
     * @return array
     */
    protected function getCacheTags() {
        if (is_array($this->override_cache_tags)) {
            return $this->override_cache_tags;
        }
        return static::$cache_tags;
    }

    /**
     * @param $id_key
     * @return $this
     */
    protected function setIdKey($id_key) {
        $this->id_key = $id_key;
        return $this;
    }

    /**
     * @param string $query
     * @param array $args
     * @return mixed
     */
    protected function doInsert($query, array $args = []) {
        if (!$this->validate()) {
            return false;
        }
        return $this->getQuery()->setLastInsertIdFieldName($this->id_key)->insert($query, $args + $this->exportArray());
    }

}
