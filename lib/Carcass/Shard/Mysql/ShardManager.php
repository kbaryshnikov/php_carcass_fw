<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Corelib;
use Carcass\Connection;
use Carcass\Config;
use Carcass\Application\DI;
use Carcass\Mysql;

/**
 * Class Mysql_ShardManager
 * @package Carcass\Shard
 */
class Mysql_ShardManager {

    /**
     * @var Mysql\Client|null
     */
    protected $ShardingDb = null;
    /**
     * @var Mysql\HandlerSocket_Connection
     */
    protected $ShardingHsConnection = null;
    /**
     * @var Mysql_ShardingModel|null
     */
    protected $Model = null;
    /**
     * @var Config\ItemInterface|null
     */
    protected $Config = null;

    public function __construct(Config\ItemInterface $Config) {
        $this->Config = $Config;
    }

    /**
     * @param int $shard_id
     * @return Mysql_Shard
     */
    public function getShardById($shard_id) {
        return $this->getModel()->getShardById($shard_id);
    }

    /**
     * @param int $server_id
     * @return Mysql_Server
     */
    public function getServerById($server_id) {
        return $this->getModel()->getServerById($server_id);
    }

    /**
     * @param Mysql_Server $Server
     * @return Mysql_Server
     */
    public function addServer(Mysql_Server $Server) {
        $this->fillServerWithDefaults($Server);
        return $this->getModel()->addServer($Server);
    }

    /**
     * @param int $db_index
     * @return string
     */
    public function getShardDbNameByIndex($db_index) {
        return $this->Config->get('shard_dbname_prefix', 'Db') . $db_index;
    }

    /**
     * @return \Carcass\Mysql\Client
     */
    public function getShardingDb() {
        if (null === $this->ShardingDb) {
            $this->ShardingDb = new Mysql\Client($this->assembleShardingDbConnection());
        }
        return $this->ShardingDb;
    }

    public function getServerIterator() {
        return new Mysql_ServerIterator($this->getModel());
    }

    public function getShardIterator(Mysql_Server $Server) {
        return new Mysql_ShardIterator($this->getModel(), $Server->getId());
    }

    /**
     * Allocates shard for $Unit. Tries to find the best shard, or if there's no shard
     * available, creates a new shard.
     *
     * If a new shard has been created, the initializeShard($Shard) unit method will be called.
     * A unit must unitialize the shard it has just received.
     *
     * Finally, the setShard($Shard) unit method will be called.
     * A unit must update its persistent state to know which shard has been allocated for it later.
     *
     * @param UnitInterface $Unit
     * @return ShardInterface
     */
    public function allocateShard(UnitInterface $Unit) {
        return $this->getModel()->allocateShard($Unit);
    }

    /**
     * @return \Carcass\Connection\ConnectionInterface|null
     */
    public function getShardingHsConnection() {
        if (null === $this->ShardingHsConnection) {
            $this->ShardingHsConnection = $this->assembleShardingHsConnection();
        }
        return $this->ShardingHsConnection;
    }

    /**
     * @param bool $drop_existing_tables
     * @return $this
     */
    public function initializeShardingDatabase($drop_existing_tables = false) {
        return $this->getModel()->initializeShardingDatabase($drop_existing_tables);
    }

    /**
     * @return Mysql_ShardingModel
     */
    protected function getModel() {
        if (null === $this->Model) {
            $this->Model = new Mysql_ShardingModel($this);
        }
        return $this->Model;
    }

    /**
     * @return Config\ItemInterface
     */
    public function getConfig() {
        return $this->Config;
    }

    /**
     * @param Mysql_Server $Server
     */
    protected function fillServerWithDefaults(Mysql_Server $Server) {
        $server_defaults = $this->Config->exportArrayFrom('server_defaults');
        if ($server_defaults) {
            $Server->fetchFromArray($this->Config->exportArrayFrom('server_defaults'), true);
        }
    }

    /**
     * @return \Carcass\Mysql\Connection
     */
    protected function assembleShardingDbConnection() {
        return DI::getConnectionManager()->getConnection($this->getShardingDbConnectionDsn());
    }

    protected function assembleShardingHsConnection() {
        return DI::getConnectionManager()->getConnection($this->getShardingHsDsn());
    }

    protected function getShardingDbConnectionDsn() {
        return $this->Config->get('sharding_database')->get('mysql_dsn');
    }

    protected function getShardingHsDsn() {
        return $this->Config->get('sharding_database')->get('hs_dsn');
    }

}
