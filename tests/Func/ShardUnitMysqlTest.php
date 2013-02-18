<?php

use Carcass\Shard;
use Carcass\Application\Injector;

class ShardUnitMysqlTest extends PHPUnit_Framework_TestCase {

    protected
        $ShardConfig,
        $ShardManager;

    public function setUp() {
        init_app();
        $this->ShardConfig = Injector::getConfigReader()->sharding;
        $this->ShardManager = new Mysql_ShardManager($this->ShardConfig);
    }

    public function testAllocate() {
        $this->resetShardTables();
    }

}
