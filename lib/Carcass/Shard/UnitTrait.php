<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Application\Injector;

/**
 * Partial UnitInterface implementation
 *
 * User must implement:
 * @method int getShardId()
 * @method UnitInterface updateShard(ShardInterface $OldShard = null)
 *
 * @package Carcass\Shard
 */
trait UnitTrait {

    /**
     * @var ShardInterface
     */
    protected $Shard = null;
    /**
     * @var Mysql_Client
     */
    protected $Database = null;
    /**
     * @var \Carcass\Memcached\Connection
     */
    protected $MemcachedConnection = null;

    /**
     * @return null
     */
    public function getShard() {
        if (null === $this->Shard) {
            $this->Shard = $this->getShardManager()->getShardById($this->getShardId());
        }
        return $this->Shard;
    }

    /**
     * @param ShardInterface $Shard
     * @return mixed
     */
    public function setShard(ShardInterface $Shard) {
        $OldShard = $this->Shard;
        $this->Shard = $Shard;
        return $this->updateShard($OldShard);
    }

    /**
     * @return Mysql_Client|null
     */
    public function getDatabase() {
        if (null === $this->Database) {
            $this->Database = new Mysql_Client($this);
        }
        return $this->Database;
    }

    /**
     * @return Mysql_ShardManager
     */
    public function getShardManager() {
        static $ShardManager = null;
        if (null === $ShardManager) {
            $ShardManager = new Mysql_ShardManager(Injector::getConfigReader()->get('sharding'));
        }
        return $ShardManager;
    }

    /**
     * @return \Carcass\Connection\ConnectionInterface|null
     */
    public function getMemcachedConnection() {
        if (null === $this->MemcachedConnection) {
            $this->MemcachedConnection = Injector::getConnectionManager()
                ->getConnection(Injector::getConfigReader()->getPath('memcached.pool'));
        }
        return $this->MemcachedConnection;
    }

}
