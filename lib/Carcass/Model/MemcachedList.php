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
 * @method \Carcass\Query\Memcached getQuery()
 *
 * @package Carcass\Model
 */
abstract class MemcachedList implements \Iterator, \ArrayAccess, \Countable, Corelib\ExportableInterface, Corelib\RenderableInterface, Query\ListReceiverInterface {
    use ListTrait, MemcachedTrait {
        MemcachedTrait::assembleQuery as mctAssembleQuery;
        ListTrait::assembleQuery as ltAssembleQuery;
    }

    protected static $default_cache_chunk_size = 10;
    protected $cache_chunk_size = null;

    protected function getCacheChunkSize() {
        return $this->cache_chunk_size;
    }

    /**
     * @return \Carcass\Query\Memcached
     */
    protected function assembleQuery() {
        return $this->mctAssembleQuery();
    }

}