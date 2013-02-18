<?php

namespace Carcass\Shard;

use Carcass\Corelib;
use Carcass\Connection;
use Carcass\Application\Injector;
use Carcass\Database\Factory as DatabaseFactory;

class Mysql_ShardManager {

    protected
        $ShardingDbConnection = null,
        $ShardingHsConnection = null,
        $Model = null,
        $Config = null;

    public function __construct(Corelib\DatasourceInterface $Config) {
        $this->Config = $Config;
    }

    public function getShardById($shard_id) {
        return $this->getModel()->getShardById($shard_id);
    }

    public function getServerById($server_id) {
        return $this->getModel()->getServerById($server_id);
    }

    public function getShardDbNameByIndex($db_index) {
        Carcass_Assert::isValidId($db_index);
        return 'Db' . $index;
    }

    public function getShardingDbConnection() {
        if (null === $this->ShardingDbConnection) {
            $this->ShardingDbConnection = $this->assembleShardingDbConnection();
        }
        return $this->ShardingDbConnection;
    }

    public function getShardingHsConnection() {
        if (null === $this->ShardingHsConnection) {
            $this->ShardingHsConnection = $this->assembleShardingHsConnection();
        }
        return $this->ShardingHsConnection;
    }

    public function getModel() {
        if (null === $this->Model) {
            $this->Model = new Mysql_ShardingModel($this);
        }
        return $this->Model;
    }

    public function getConfig() {
        return $this->Config;
    }

    protected function assembleShardingDbConnection() {
        return Injector::getConnectionManager()->getConnection($this->getShardingDbConnectionDsn());
    }

    protected function assembleShardingHsConnection() {
        return Injector::getConnectionManager()->getConnection($this->getShardingHsDsn());
    }

    protected function getShardingDbConnectionDsn() {
        return $this->Config->mysql_dsn;
    }

    protected function getShardingHsDsn() {
        return $this->Config->hs_dsn;
    }

}
