<?php

use Carcass\Shard;
use Carcass\Application\Injector;
use Carcass\Corelib;

class TestShardUnit extends Corelib\Hash implements Shard\UnitInterface {
    use Shard\UnitTrait;

    public function loadById($id) {
        return $this->merge(self::$map[$id]);
    }

    public function getId() {
        return $this->id;
    }

    public function getKey() {
        return 'shardTest';
    }

    public function getShardId() {
        return $this->shard_id;
    }

    public function updateShard($OldShard) {
        if (null !== $OldShard) {
            throw new \LogicException("Migration between shards is not implemented");
        }
        $this->shard_id = $this->Shard->getId();
        self::$map[$this->id]['shard_id'] = $this->shard_id;
    }

    public function initializeShard() {
        $Db = $this->getDatabase();
        $Db->createSequenceTable(true);
        $Db->query(
            "CREATE TABLE {{ t('Test') }} (
                {{ name(_unit_key) }} integer unsigned NOT NULL,
                test_id integer unsigned NOT NULL,
                test_value varchar(255) NOT NULL,
                PRIMARY KEY ({{ name(_unit_key) }}, test_id)
            ) Engine=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    public static $map = [
        1 => [
            'id' => 1,
            'shard_id' => 1,
        ],
    ];
}

class ShardUnitMysqlTest extends PHPUnit_Framework_TestCase {

    protected static $pw = [
        'username' => 'test',
        'password' => 'test',
        'super_username' => 'root',
        'super_password' => '890p',
    ];

    protected
        $ShardConfig,
        $ShardManager;

    public function setUp() {
        init_app();
        /** @noinspection PhpUndefinedFieldInspection */
        $this->ShardConfig = Injector::getConfigReader()->sharding;
        $this->ShardManager = new Shard\Mysql_ShardManager($this->ShardConfig);
    }

    public function testShardUnit() {
        $this->ShardManager->getModel()->initializeShardingDatabase(true);

        $Server = new Shard\Mysql_Server( ['ip_address' => '127.0.0.1'] + self::$pw );
        $this->ShardManager->getModel()->addServer($Server);
    }

}
