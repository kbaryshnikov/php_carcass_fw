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
        $this->id = $id;
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
        return $this->getDatabaseClient();
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
        $Db = $this->getDatabaseClient();
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

class TestListShardModel extends Shard\ListModel {

    const MC_TAG = 'TList';

    const DEFAULT_LIMIT = 100;

    protected static
        $cache_key = 'tList',
        $cache_tags = [self::MC_TAG];

    public $chunk_size = null;

    protected static function getItemModelClass() {
        return '\TestShardModel';
    }

    public function load($limit = self::DEFAULT_LIMIT, $offset = 0) {
        $this->getQueryDispatcher()
            ->setListChunkSize($this->chunk_size)
            ->setLimit($limit, $offset)
            ->fetchList(
                "SELECT
                    {{ IF COUNT }}
                        COUNT(id)
                    {{ END }}
                    {{ UNLESS COUNT }}
                        id, email
                    {{ END }}
                FROM
                    {{ t('t') }}
                {{ where() }}
                    1 = 1
                {{ UNLESS COUNT }}
                    ORDER BY id
                    {{ limit(limit, offset) }}
                {{ END }}"
            )
            ->execute()
            ->sendListTo($this);
        return $this;
    }

}

class TestShardModel extends Shard\Model {

    protected static
        $cache_key = 't_{{ i(id) }}',
        $cache_tags = ['T_{{ i(id) }}', TestListShardModel::MC_TAG];

    protected static $sequence = ['t' => 'id'];

    public static function getModelRules() {
        return [
            'id'    => ['isValidId'],
            'email' => ['isNotEmpty', 'isValidEmail']
        ];
    }

    public function getDb() {
        return $this->getQueryDispatcher()->getDatabaseClient();
    }

    public function getMct() {
        return $this->getQueryDispatcher()->getMct();
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

    protected $ShardingDb;

    public function setUp() {
        init_app();
        $this->ShardConfig = DI::getConfigReader()->get('sharding');
        $this->ShardManager = new Shard\Mysql_ShardManager($this->ShardConfig);
    }

    public function testShard() {
        $this->ShardManager->initializeShardingDatabase(true);

        $ShardingConfig = DI::getConfigReader()->get('sharding');

        /** @var $ShardingDbConn \Carcass\Mysql\Connection */
        $ShardingDbConn = DI::getConnectionManager()->getConnection($ShardingConfig->getPath('sharding_database.mysql_dsn'));
        $ShardingDb = new \Carcass\Mysql\Client($ShardingDbConn);

        $this->ShardingDb = $ShardingDb;

        $Server = $this->addServer();

        $this->assertEquals($ShardingConfig->getPath('server_defaults.capacity'), $Server->capacity);
        $this->assertEquals($ShardingConfig->getPath('server_defaults.units_per_shard'), $Server->units_per_shard);

        $server_row = $ShardingDb->getRow("SELECT * FROM DatabaseServers WHERE database_server_id = {{ i(id) }}", ['id' => $Server->getId()]);
        $this->assertEquals($ShardingConfig->getPath('server_defaults.capacity'), $server_row['capacity']);
        $this->assertEquals($ShardingConfig->getPath('server_defaults.units_per_shard'), $server_row['units_per_shard']);

        // test unit

        $Unit1 = new TestShardUnit(1);

        /** @var $Shard Shard\Mysql_Shard */
        $Shard = $this->ShardManager->allocateShard($Unit1);

        $this->assertEquals(1, $Shard->units_allocated);
        $this->assertEquals($Server->units_per_shard - 1, $Shard->units_free);

        $shard_row = $ShardingDb->getRow("SELECT * FROM DatabaseShards WHERE database_shard_id = {{ i(id) }}", ['id' => $Shard->getId()]);
        $this->assertEquals(1, $shard_row['units_allocated']);
        $this->assertEquals($Server->units_per_shard - 1, $shard_row['units_free']);

        $this->assertTrue($Unit1->initialize_shard_called);
        $this->assertEquals(1, $Shard->getId());
        $this->assertEquals(['Seq1'], $Unit1->getDatabaseClient()->getCol("show tables in TestShardDb1 like 'Seq1'"));
        $this->assertEquals(['Test1'], $Unit1->getDatabaseClient()->getCol("show tables in TestShardDb1 like 'Test1'"));

        $Unit2 = new TestShardUnit(2);
        $Shard = $this->ShardManager->allocateShard($Unit2);

        $this->assertFalse($Unit2->initialize_shard_called);

        $this->assertEquals(2, $Shard->units_allocated);
        $this->assertEquals($Server->units_per_shard - 2, $Shard->units_free);

        $shard_row = $ShardingDb->getRow("SELECT * FROM DatabaseShards WHERE database_shard_id = {{ i(id) }}", ['id' => $Shard->getId()]);
        $this->assertEquals(2, $shard_row['units_allocated']);
        $this->assertEquals($Server->units_per_shard - 2, $shard_row['units_free']);

        $this->assertEquals(1, $Shard->getId());
        $this->assertEquals(['Seq1'], $Unit2->getDatabaseClient()->getCol("show tables in TestShardDb1 like 'Seq1'"));
        $this->assertEquals(['Test1'], $Unit2->getDatabaseClient()->getCol("show tables in TestShardDb1 like 'Test1'"));

        $this->assertSame($Unit1->getDatabaseClient()->getDsn(), $Unit2->getDatabaseClient()->getDsn());
        $this->assertNotSame($Unit1->getDatabaseClient(), $Unit2->getDatabaseClient());

        // test model

        $Model = new TestShardModel($Unit1);
        $this->assertFalse($Model->isLoaded());
        $Model->fetchFromArray(
            [
                'email' => '1@domain.com'
            ]
        );
        $this->assertTrue($Model->validate());
        $Model->insert();
        $this->assertTrue($Model->isLoaded());
        $this->assertEquals(1, $Model->id);

        $seq_value = $Unit1->getDatabaseClient()->getCell("select value from TestShardDb1.Seq1 where shardTest=1 and name='t'");
        $this->assertEquals(1, $seq_value);

        $row = $Unit1->getDatabaseClient()->getRow("select * from TestShardDb1.t1 where shardTest=1 and id=1");
        $this->assertEquals('1@domain.com', $row['email']);

        unset($Model);

        $Model = new TestShardModel($Unit1);
        $Model->loadById(1);
        $this->assertTrue($Model->isLoaded());
        $this->assertEquals(1, $Model->id);
        $this->assertEquals('1@domain.com', $Model->email);

        $Model->fetchFromArray(
            [
                'email' => 'a@domain.com'
            ]
        );
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

        // test list model

        for ($i = 1; $i <= 20; ++$i) {
            (new TestShardModel($Unit1))->fetchFromArray(['email' => "$i@domain.com"])->insert();
        }

        $ListModel = new TestListShardModel($Unit1);
        $ListModel->load(15);

        $this->assertEquals(20, $ListModel->getCount());
        $this->assertEquals(15, count($ListModel));

        $i = 0;
        foreach ($ListModel as $ItemModel) {
            $i++;
            $this->assertEquals("$i@domain.com", $ItemModel->email);
        }

        // add fake servers and shards to test iterators
        $Servers = [$Server, $Server2 = $this->addServer(2, false), $this->addServer(3, false)];

        $this->ShardingDb->query("INSERT INTO DatabaseShards (database_server_id, database_idx, units_free) VALUES (1, 1, 100)");
        $this->ShardingDb->query("INSERT INTO DatabaseShards (database_server_id, database_idx, units_free) VALUES (2, 1, 100)");
        $this->ShardingDb->query("INSERT INTO DatabaseShards (database_server_id, database_idx, units_free) VALUES (2, 1, 100)");
        $this->ShardingDb->query("INSERT INTO DatabaseShards (database_server_id, database_idx, units_free) VALUES (2, 1, 100)");
        $this->ShardingDb->query("INSERT INTO DatabaseShards (database_server_id, database_idx, units_free) VALUES (3, 1, 100)");

        // test server iterator
        /** @var $Servers Shard\Mysql_Server[] */
        for ($i = 0; $i < 2; ++$i) {
            /** @var $Srv Shard\Mysql_Server */
            $idx = 0;
            foreach ($this->ShardManager->getServerIterator() as $idx => $Srv) {
                $this->assertInstanceOf('\Carcass\Shard\Mysql_Server', $Srv);
                $this->assertEquals($Servers[$idx]->getId(), $Srv->getId());
            }
            $this->assertEquals(2, $idx);
        }

        // test shard iterator
        for ($i = 0; $i < 2; ++$i) {
            $idx = null;
            foreach ($this->ShardManager->getShardIterator($Server) as $idx => $Shard) {
                $this->assertInstanceOf('\Carcass\Shard\Mysql_Shard', $Shard);
                $this->assertEquals($idx+1, $Shard->getId(), $i);
                $this->assertEquals(1, $Shard->getServer()->getId(), $i);
            }
            $this->assertEquals(1, $idx);
        }

        for ($i = 0; $i < 2; ++$i) {
            $idx2 = null;
            foreach ($this->ShardManager->getShardIterator($Server2) as $idx2 => $Shard) {
                $this->assertInstanceOf('\Carcass\Shard\Mysql_Shard', $Shard);
                $this->assertEquals($Server2->getDsn(), $Shard->getServer()->getDsn(), $i);
                $this->assertEquals($Server2->getId(), $Shard->getServer()->getId(), $i);
            }
            $this->assertEquals(2, $idx2);
        }
    }

    protected function addServer($id = 1, $delete_databases = true) {
        $Server = new Shard\Mysql_Server(['ip_address' => '127.0.0.1'] + self::$pw);
        $Server = $this->ShardManager->addServer($Server);

        $this->assertEquals($id, $Server->getId());

        $delete_databases and $this->deleteAllShardingDatabasesFrom($Server);

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
