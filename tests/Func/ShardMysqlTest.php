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
        $Db->query(
            "CREATE TABLE {{ t('t') }} (
                {{ name(_unit_key) }} integer unsigned NOT NULL,
                id integer unsigned NOT NULL,
                email varchar(255),
                PRIMARY KEY ({{ name(_unit_key) }}, id)
            ) Engine=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->initialize_shard_called = true;
    }

    public static $map = [];
}

class TestShardModel extends Shard\Model {

    protected static
        $cache_key = 't_{{ i(id) }}',
        $cache_tags = [ 'T_{{ i(id) }}' ];

    protected static $sequence = ['t' => 'id'];

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

    public function loadById($id) {
        $this->doFetch('SELECT id, email FROM {{ t("t") }} {{ where() }} id = {{ i(id) }}', compact('id'));
        return $this->isLoaded();
    }

    public function insert() {
        return $this->doInsert('INSERT INTO {{ t("t") }} {{ set() }} id = {{ i(id) }}, email = {{ s(email) }}');
    }

    public function update() {
        return $this->doModify('UPDATE {{ t("t") }} SET email = {{ s(email) }} {{ where() }} id = {{ i(id) }}');
    }

    public function delete() {
        return $this->doModify('DELETE FROM {{ t("t") }} {{ where() }} id = {{ i(id) }}');
    }

}

class ShardMysqlTest extends PHPUnit_Framework_TestCase {

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

    public function testShard() {
        $this->ShardManager->initializeShardingDatabase(true);

        $Server = $this->addServer();

        // test unit

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

        // test model

        $Model = new TestShardModel($Unit1);
        $this->assertFalse($Model->isLoaded());
        $Model->fetchFromArray([
            'email' => '1@domain.com'
        ]);
        $this->assertTrue($Model->validate());
        $Model->insert();
        $this->assertTrue($Model->isLoaded());
        $this->assertEquals(1, $Model->id);

        $seq_value = $Unit1->getDatabase()->getCell("select value from TestShardDb1.Seq1 where shardTest=1 and name='t'");
        $this->assertEquals(1, $seq_value);

        $row = $Unit1->getDatabase()->getRow("select * from TestShardDb1.t1 where shardTest=1 and id=1");
        $this->assertEquals('1@domain.com', $row['email']);

        unset($Model);

        $Model = new TestShardModel($Unit1);
        $Model->loadById(1);
        $this->assertTrue($Model->isLoaded());
        $this->assertEquals(1, $Model->id);
        $this->assertEquals('1@domain.com', $Model->email);

        $Model->fetchFromArray([
            'email' => 'a@domain.com'
        ]);
        $Model->update();
        $this->assertEquals('a@domain.com', $Model->email);

        unset($Model);

        $Model = new TestShardModel($Unit1);
        $Model->loadById(1);
        $this->assertEquals(1, $Model->id);
        $this->assertEquals('a@domain.com', $Model->email);
        $Model->delete();

        unset($Model);

        $Model = new TestShardModel($Unit1);
        $Model->loadById(1);
        $this->assertFalse($Model->isLoaded());
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
