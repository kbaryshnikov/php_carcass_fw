<?php

namespace Carcass\Shard;

use Carcass\Corelib;
use Carcass\Application\Injector;

class Mysql_Shard implements ShardInterface {

    protected
        $Server = null,
        $MysqlConnection = null,
        $ShardManager;

    public function __construct(Mysql_ShardManager $ShardManager, array $shard_data = []) {
        $this->ShardManager = $ShardManager;
        parent::__construct($shard_data);
    }

    public function getServer() {
        if (null === $this->Server) {
            $server_id = $this->get('database_server_id');
            if (!$server_id) {
                throw new \LogicException('database_server_id is undefined');
            }
            $this->Server = $this->ShardManager->getServerManager()->getServerById($server_id);
        }
        return $this->Server;
    }

    public function getDatabaseConnection() {
        if (null === $this->MysqlConnection) {
            $this->MysqlConnection = Injector::getConnectionManager()->getConnectionByDsn(
                $this->getServer()->getDsn()
            );
        }
        return $this->MysqlConnection;
    }

}
