<?php

namespace Carcass\Shard;

use Carcass\Application\Injector;

trait UnitTrait {

    protected
        $Shard = null,
        $Database = null,
        $MemcachedConnection = null;

    public function getShard() {
        if (null === $this->Shard) {
            $this->Shard = $this->getShardManager()->getShardById($this->getShardId());
        }
        return $this->Shard;
    }

    public function setShard(ShardInterface $Shard) {
        $OldShard = $this->Shard;
        $this->Shard = $Shard;
        return $this->updateShard($OldShard);
    }

    public function getDatabase() {
        if (null === $this->Database) {
            $this->Database = new Mysql_Client($this);
        }
        return $this->Database;
    }

    public function getShardManager() {
        static $ShardManager = null;
        if (null === $ShardManager) {
            $ShardManager = new Mysql_ShardManager(Injector::getConfigReader()->sharding);
        }
        return $ShardManager;
    }

    public function getMemcachedConnection() {
        if (null === $this->MemcachedConnection) {
            $this->MemcachedConnection = Injector::getConnectionManager()
                ->getConnection(Injector::getConfigReader()->memcached->pool);
        }
        return $this->MemcachedConnection;
    }

}
