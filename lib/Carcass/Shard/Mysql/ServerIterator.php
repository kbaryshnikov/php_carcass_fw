<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

/**
 * Server iterator, should be instantiated by Manager->getServerIterator(), and NOT directly
 *
 * @package Carcass\Shard
 */
class Mysql_ServerIterator implements \Iterator {

    /** @var Mysql_ShardingModel */
    protected $Model;

    /** @var Mysql_Server[] */
    protected $servers = [];

    protected $last_server_id = 0;
    protected $position = 0;

    protected $total_count = null;

    public function __construct(Mysql_ShardingModel $Model) {
        $this->Model = $Model;
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
        $Server = $this->Model->getNextServer($this->last_server_id);
        if (!$Server) {
            $this->total_count = count($this->servers);
            return false;
        }

        if (null === $position) {
            $this->servers[] = $Server;
        } else {
            $this->servers[$position] = $Server;
        }

        $this->last_server_id = $Server->getId();

        return $Server;
    }

    /**
     * @return Mysql_Server|bool false
     */
    public function current() {
        if (!isset($this->servers[$this->position]) && !$this->total_count) {
            if (!$this->loadNext($this->position)) {
                return false;
            }
        }

        return $this->servers[$this->position];
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
        if (!isset($this->servers[$this->position])) {
            if (!$this->total_count) {
                return $this->loadNext($this->position);
            }
        }
        return true;
    }

}