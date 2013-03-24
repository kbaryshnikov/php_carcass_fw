<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

/**
 * Shard iterator, should be instantiated by Manager->getShardIterator(), and NOT directly
 *
 * @package Carcass\Shard
 */
class Mysql_ShardIterator implements \Iterator {

    /** @var Mysql_ShardingModel */
    protected $Model;

    /** @var Mysql_Shard[] */
    protected $shards = [];

    protected $server_id;

    protected $last_shard_id = 0;
    protected $position = 0;

    protected $total_count = null;

    public function __construct(Mysql_ShardingModel $Model, $server_id) {
        $this->Model = $Model;
        $this->server_id = $server_id;
    }

    /**
     * @return void
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * @return void
     */
    public function next() {
        ++$this->position;
    }

    protected function loadNext($position = null) {
        $Shard = $this->Model->getNextShard($this->server_id, $this->last_shard_id);
        if (!$Shard) {
            $this->total_count = count($this->shards);
            return false;
        }

        if (null === $position) {
            $this->shards[] = $Shard;
        } else {
            $this->shards[$position] = $Shard;
        }

        $this->last_shard_id = $Shard->getId();

        return $Shard;
    }

    /**
     * @return Mysql_Shard|bool false
     */
    public function current() {
        if (!isset($this->shards[$this->position]) && !$this->total_count) {
            if (!$this->loadNext($this->position)) {
                return false;
            }
        }

        return $this->shards[$this->position];
    }

    /**
     * @return mixed
     */
    public function key() {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function valid() {
        if (!isset($this->shards[$this->position])) {
            if (!$this->total_count) {
                return $this->loadNext($this->position);
            }
        }
        return true;
    }

}