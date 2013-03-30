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
abstract class MemcachedList implements \Iterator, \ArrayAccess, \Countable, Corelib\ExportableInterface, Corelib\RenderableInterface, Query\ListReceiverInterface {
    use ListTrait, MemcachedTrait {
        MemcachedTrait::assembleQueryDispatcher insteadof ListTrait;
    }

    protected static $default_cache_chunk_size = 10;

    protected $cache_chunk_size = null;

    protected function getCacheChunkSize() {
        return $this->cache_chunk_size;
    }

}