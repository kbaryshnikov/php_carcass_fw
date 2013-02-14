<?php

namespace Carcass\Shard;

interface UnitInterface {

    public function loadById($id);

    public function getId();

    public function getShardId();

    public function getKey();

    public function setShardId($shard_id);

}
