<?php

use Carcass\Shard;
use Carcass\Application\DI;
use Carcass\Corelib;
use Carcass\Config;
use Carcass\Mysql;

class TestShardUnit extends Corelib\Hash implements Shard\UnitInterface {
    use Shard\UnitTrait;

    public $initialize_shard_called = false;

    public function __construct($id) {
        $this->id       = $id;
        self::$map[$id] = ['id' => $id];
    }

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

    /**
     * @return Shard\Mysql_Client
     */
    public function getDb() {
        return $this->getDatabase();
    }

    public function updateShard($OldShard) {
        if (null !== $OldShard) {
            throw new \LogicException("Migration between shards is not implemented");
        }

        if (isset(self::$map[$this->id]['shard_id'])) {
            throw new \LogicException("Shard is already allocated");
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
        $this->initialize_shard_called = true;
    }

    public static $map = [];
}

class ShardUnitMysqlTest extends PHPUnit_Framework_TestCase {

    protected static $pw = [
        'username'       => 'test',
        'password'       => 'test',
        'super_username' => 'root',
        'super_password' => '890p',
    ];

    /**
     * @var Config\ItemInterface
     */
    protected $ShardConfig;
    /**
     * @var Shard\Mysql_ShardManager
     */
    protected $ShardManager;

    public function setUp() {
        init_app();
        $this->ShardConfig  = DI::getConfigReader()->get('sharding');
        $this->ShardManager = new Shard\Mysql_ShardManager($this->ShardConfig);
    }

    public function testShardUnits() {
        $this->ShardManager->initializeShardingDatabase(true);

        $Server = $this->addServer();

        $Unit1 = new TestShardUnit(1);
        $Shard = $this->ShardManager->allocateShard($Unit1);
        $this->assertTrue($Unit1->initialize_shard_called);
        $this->assertEquals(1, $Shard->getId());
        $this->assertEquals(['Seq1'], $Unit1->getDatabase()->getCol("show tables in TestShardDb1 like 'Seq1'"));
        $this->assertEquals(['Test1'], $Unit1->getDatabase()->getCol("show tables in TestShardDb1 like 'Test1'"));

        $Unit2 = new TestShardUnit(2);
        $Shard = $this->ShardManager->allocateShard($Unit2);
        $this->assertFalse($Unit2->initialize_shard_called);
        $this->assertEquals(1, $Shard->getId());
        $this->assertEquals(['Seq1'], $Unit2->getDatabase()->getCol("show tables in TestShardDb1 like 'Seq1'"));
        $this->assertEquals(['Test1'], $Unit2->getDatabase()->getCol("show tables in TestShardDb1 like 'Test1'"));

        $this->assertSame($Unit1->getDatabase()->getDsn(), $Unit2->getDatabase()->getDsn());
        $this->assertNotSame($Unit1->getDatabase(), $Unit2->getDatabase());
    }

    protected function addServer() {
        $Server = new Shard\Mysql_Server(['ip_address' => '127.0.0.1'] + self::$pw);
        $Server = $this->ShardManager->addServer($Server);

        $this->assertEquals(1, $Server->getId());

        $this->deleteAllShardingDatabasesFrom($Server);

        return $Server;
    }

    protected function deleteAllShardingDatabasesFrom(Shard\Mysql_Server $Server) {
        /** @var $Connection Mysql\Connection */
        $Connection = DI::getConnectionManager()->getConnection($Server->getSuperDsn());

        $Db = new Mysql\Client($Connection);

        $databases = $Db->getCol(
            "SHOW DATABASES LIKE {{ s(shard_db_mask) }}", [
                'shard_db_mask' => $this->ShardManager->getShardDbNameByIndex('%')
            ]
        );

        foreach ($databases as $db_name) {
            $Db->query("DROP DATABASE {{ name(db_name) }}", ['db_name' => $db_name]);
        }
    }

}
