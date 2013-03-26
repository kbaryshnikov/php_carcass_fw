<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
namespace Carcass\Model;

use Carcass\Corelib;
use Carcass\Query;

/**
 * Memcached list model
 *
 * @method \Carcass\Query\MemcachedDispatcher getQueryDispatcher()
 *
 * @package Carcass\Model
 */
abstract class MemcachedList extends ListBase implements \Iterator, \ArrayAccess, \Countable, Corelib\ExportableInterface, Corelib\RenderableInterface, Query\ListReceiverInterface {
    use MemcachedQueryTrait;

    protected static $default_cache_chunk_size = 10;

    protected $cache_chunk_size = null;

    protected function getCacheChunkSize() {
        return $this->cache_chunk_size;
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
        return parent::prepareQueryDispatcher($QueryDispatcher);
    }

}