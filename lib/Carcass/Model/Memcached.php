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
use Carcass\Corelib;

/**
 * Memcached Model
 *
 * @method \Carcass\Query\MemcachedDispatcher getQueryDispatcher()
 *
 * @package Carcass\Model
 */
abstract class Memcached extends Base implements Corelib\DatasourceInterface, Corelib\DataReceiverInterface, Corelib\ImportableInterface, Corelib\ExportableInterface, Corelib\RenderableInterface {
    use MemcachedQueryTrait;

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
        return $this->getQueryDispatcher()->setLastInsertIdFieldName($this->id_key)->insert($query, $args + $this->exportArray());
    }

    protected function assembleQueryDispatcher() {
        return new Query\MemcachedDispatcher;
    }

    protected function prepareQueryDispatcher(Query\BaseDispatcher $QueryDispatcher) {
        if (!$QueryDispatcher instanceof Query\MemcachedDispatcher) {
            throw new \LogicException("instanceof Query\\MemcachedDispatcher expected");
        }
        $this->configureBaseQueryDispatcher($QueryDispatcher);
        $this->configureMemcachedQueryDispatcher($QueryDispatcher);
        return $QueryDispatcher;
    }

}
