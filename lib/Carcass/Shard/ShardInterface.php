<?php

namespace Carcass\Shard;

interface ShardInterface {

    public function getId();

    public function getDsn();

    public function getDatabaseName();

}
