<?php

/*

use Carcass\Model;

use Carcass\Application\DI;

use Carcass\Shard;

class TestShardModel extends Shard\Model {

    protected static
        $cache_key = 'foo_{{ i(foo_id) }}',
        $cache_tags = [ 'Foo_{{ i(foo_id) }}' ];

    public static function getModelRules() {
        return [
            'id'        => [ 'isValidId' ],
            'email'     => [ 'isNotEmpty', 'isValidEmail' ]
        ];
    }

    public function getDb() {
        return $this->getQuery()->getDatabase();
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

    public function createTable() {
        $this->doModify("
            CREATE TABLE {{ t('t') }} (
                {{ name(_unit_key) }} integer unsigned NOT NULL,
                id integer unsigned NOT NULL,
                email varchar(255),
                PRIMARY KEY ({{ name(_unit_key) }}, id)
            ) ENGINE=InnoDB
        ");
    }

    public function loadById($id) {
        $this->doFetch('SELECT id, email FROM {{ table("t") }} {{ where() }} id = {{ i(id) }}', compact('id'));
        return $this->isLoaded();
    }

    public function insert() {
        return $this->doInsert('INSERT INTO {{ table("t") }} {{ set() }} email = {{ s(email) }}', [], 'id');
    }

    public function update() {
        return $this->doModify('UPDATE {{ table("t") }} SET email = {{ s(email) }} {{ where() }} id = {{ i(id) }}');
    }

    public function delete() {
        return $this->doModify('DELETE FROM {{ table("t") }} {{ where() }} id = {{ i(id) }}');
    }

}

class TestShardUnit implements Shard\UnitInterface {

    public static $map = [];

    const SHARD_KEY = 'test_id';

    public $id = null;
    public $shard_id = null;

    public function __construct($id = null) {
        $id and $this->loadById($id);
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

    public function getDatabaseName() {
        return 'TestShardDatabase';
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
        $this->tstUnitModel();
    }

    protected function allocateShards() {
        $Allocator = $this->Factory->getAllocator('mysql');
        $Allocator->initShardingTables();
        $server_id = $Allocator->addServer([
            'ip_address' => '127.0.0.1',
            'username' => 'test',
            'password' => 'test',
            'units_per_shard' => 2,
            'management_username' => 'root',
            'management_password' => '890p',
            'user_host' => 'localhost',
            'drop_database_if_exists' => true,
        ], new TestShardUnit);
        $this->assertEquals(1, $server_id);

        $Unit = new TestShardUnit(1);
        $must_init_shard = $Allocator->allocate($Unit);
        $this->assertEquals(1, $Unit->shard_id);
        $this->assertTrue($must_init_shard);

        $Unit = new TestShardUnit(2);
        $must_init_shard = $Allocator->allocate($Unit);
        $this->assertEquals(1, $Unit->shard_id);
        $this->assertFalse($must_init_shard);

        $Unit = new TestShardUnit(3);
        $must_init_shard = $Allocator->allocate($Unit);
        $this->assertEquals(2, $Unit->shard_id);
        $this->assertTrue($must_init_shard);
    }

    protected function tstDsnMaps() {
        $Mapper = $this->Factory->getMapper('mysql');
        $this->assertEquals('mysqls://test:test@127.0.0.1:3306/TestShardDatabase', (string)$Mapper->getDsn(new TestShardUnit(1)));
        $this->assertEquals('mysqls://test:test@127.0.0.1:3306/TestShardDatabase', (string)$Mapper->getDsn(new TestShardUnit(2)));
        $this->assertEquals('mysqls://test:test@127.0.0.1:3306/TestShardDatabase', (string)$Mapper->getDsn(new TestShardUnit(3)));
    }

    protected function tstUnitModel() {
        $Model = $this->Factory->getModel('TestShardModel', new TestShardUnit(1));
        $Model->createTable();
    }

}

*/
