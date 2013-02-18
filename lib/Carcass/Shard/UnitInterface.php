<?php

namespace Carcass\Shard;

interface UnitInterface {

    public function initializeShard();

    public function loadById($id);

    public function getId();

    public function getKey();

    public function getShard();

    public function setShard(ShardInterface $Shard);

    public function getMemcachedConnection();

    public function getDatabase();

    public function getShardManager();

}
