<?php

namespace Carcass\Shard;

trait UnitTrait {

    protected
        $shard_id = null,
        $Shard = null,
        $MemcacheConnection = null;

    // must implement:
    // static::getShardManager()
    // $this->assembleMemcacheConnection()

    public function getShardId() {
        return $this->shard_id;
    }

    public function getShard() {
        if (null === $this->Shard) {
            $this->Shard = $this->assembleShard();
        }
        return $this->Shard;
    }

    public function getMemcacheConnection() {
        if (null === $this->MemcacheConnection) {
            $this->MemcacheConnection = $this->assembleMemcacheConnection();
        }
        return $this->MemcacheConnection;
    }

    protected function assembleShard() {
        if (null === $this->shard_id) {
            throw new \LogicException('shard id is undefined');
        }
        return static::getShardManager()->getShardById($this->shard_id);
    }

}
