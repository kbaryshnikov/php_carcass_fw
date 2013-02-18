<?php

namespace Carcass\Shard;

interface UnitInterface {

    public function initialize($Shard);

    public function loadById($id);

    public function getId();

    public function getKey();

    public function getShard();

    public function setShard($Shard);

    public function getMemcacheConnection();

    public static function getShardManager();

}
