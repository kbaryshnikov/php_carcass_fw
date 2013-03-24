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
    use MemcachedTrait;

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
