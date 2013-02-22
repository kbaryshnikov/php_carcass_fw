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
use Carcass\Application\Injector;
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
     * @var \Carcass\Corelib\DatasourceInterface|null
     */
    protected $Config = null;

    /**
     * @param \Carcass\Corelib\DatasourceInterface $Config
     */
    public function __construct(Corelib\DatasourceInterface $Config) {
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
     * @param int $db_index
     * @return string
     */
    public function getShardDbNameByIndex($db_index) {
        Corelib\Assert::that("'$db_index' is a valid database index")->isValidId($db_index);
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
     * @return Mysql_ShardingModel|null
     */
    public function getModel() {
        if (null === $this->Model) {
            $this->Model = new Mysql_ShardingModel($this);
        }
        return $this->Model;
    }

    /**
     * @return \Carcass\Corelib\DatasourceInterface|null
     */
    public function getConfig() {
        return $this->Config;
    }

    /**
     * @return \Carcass\Mysql\Connection
     */
    protected function assembleShardingDbConnection() {
        return Injector::getConnectionManager()->getConnection($this->getShardingDbConnectionDsn());
    }

    /**
     * @return \Carcass\Connection\ConnectionInterface
     */
    protected function assembleShardingHsConnection() {
        return Injector::getConnectionManager()->getConnection($this->getShardingHsDsn());
    }

    /**
     * @return mixed
     */
    protected function getShardingDbConnectionDsn() {
        return $this->Config->get('sharding_database')->get('mysql_dsn');
    }

    /**
     * @return mixed
     */
    protected function getShardingHsDsn() {
        return $this->Config->get('sharding_database')->get('hs_dsn');
    }

}
