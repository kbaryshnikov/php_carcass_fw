<?php

use Carcass\Model;

use Carcass\Application\Injector;

use Carcass\Shard;

class TestShardModel extends Shard\Model {
/*
    protected static
        $cache_key = 'test_{{ i(id) }}',
        $cache_tags = [ 'Test_{{ i(id) }}' ];

    public static function getModelRules() {
        return [
            'id'        => [ 'isValidId' ],
            'email'     => [ 'isNotEmpty', 'isValidEmail' ]
        ];
    }

    public function getMct() {
        return $this->getQuery()->getMct();
    }

    public function isLoaded() {
        return null !== $this->id;
    }

    public function reload() {
        if (!$this->isLoaded()) {
            throw new \LogicException("Not loaded");
        }
        return $this->loadById($this->id);
    }

    public function loadById($id) {
        $this->doFetch('SELECT id, email FROM t WHERE id = {{ i(id) }}', compact('id'));
        return $this->isLoaded();
    }

    public function insert() {
        return $this->doInsert('INSERT INTO t SET email = {{ s(email) }}', [], 'id');
    }

    public function update() {
        return $this->doModify('UPDATE t SET email = {{ s(email) }} WHERE id = {{ i(id) }}');
    }

    public function delete() {
        return $this->doModify('DELETE FROM t WHERE id = {{ i(id) }}');
    }
*/
}

class TestShardUnit implements Shard\UnitInterface {

    public static $map = [];

    const SHARD_KEY = 'test_id';

    public $id = null;
    public $shard_id = null;

    public function __construct($id) {
        $this->loadById($id);
    }

    public function loadById($id) {
        $this->id = $id;
        if (isset(self::$map[$id])) {
            $this->shard_id = self::$map[$id];
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getShardId() {
        return $this->shard_id;
    }

    public function setShardId($shard_id) {
        $this->shard_id = (int)$shard_id;
        self::$map[$this->id] = $this->shard_id;
    }

    public function getKey() {
        return self::SHARD_KEY;
    }

}

class ShardModelTest extends PHPUnit_Framework_TestCase {

    protected
        $Factory;

    public function setUp() {
        init_app();
        $this->Factory = new Shard\Factory;
    }

    public function testWorkflow() {
        $this->allocateShards();
        $this->tstDsnMaps();
    }

    protected function allocateShards() {
        $Allocator = $this->Factory->getAllocator('mysql');
        $Allocator->initShardingTables();
        $server_id = $Allocator->addServer([
            'ip_address' => '127.0.0.1',
            'username' => 'test',
            'password' => 'test',
            'units_per_shard' => 2,
        ]);
        $this->assertEquals(1, $server_id);

        $Unit = new TestShardUnit(1);
        $Allocator->allocate($Unit);
        $this->assertEquals(1, $Unit->shard_id);

        $Unit = new TestShardUnit(2);
        $Allocator->allocate($Unit);
        $this->assertEquals(1, $Unit->shard_id);

        $Unit = new TestShardUnit(3);
        $Allocator->allocate($Unit);
        $this->assertEquals(2, $Unit->shard_id);
    }

    protected function tstDsnMaps() {
        $Mapper = $this->Factory->getMapper('mysql');
        $this->assertEquals('mysqls://test:test@127.0.0.1:3306/?shard_id=1', (string)$Mapper->getDsn(new TestShardUnit(1)));
        $this->assertEquals('mysqls://test:test@127.0.0.1:3306/?shard_id=1', (string)$Mapper->getDsn(new TestShardUnit(2)));
        $this->assertEquals('mysqls://test:test@127.0.0.1:3306/?shard_id=2', (string)$Mapper->getDsn(new TestShardUnit(3)));
    }

}
