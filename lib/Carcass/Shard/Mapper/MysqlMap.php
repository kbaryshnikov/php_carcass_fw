<?php

namespace Carcass\Shard;

use Carcass\Config;
use Carcass\Connection;
use Carcass\Mysql;
use Carcass\Corelib;

class Mysql_ShardModel implements DsnMapperInterface {

    protected
        $Manager,
        $shard_database,
        $shard_table_name,
        $servers_table_name,
        $HsShardIndex = null,
        $HsServerIndex = null,
        $shard_cache = [],
        $server_cache = [];

    public function __construct(Mysql_ShardManager $Manager, array $kwargs) {
        $this->Manager = $Manager;
        $this->shard_database       = $kwargs['shard_database'];
        $this->shard_table_name     = $kwargs['shard_table'];
        $this->servers_table_name   = $kwargs['servers_table'];
    }

    public function getShardById($shard_id) {
        if (!isset($this->shard_cache[$shard_id])) {
            $shard_result = $this->getShardIndex()->find('==', ['database_shard_id' => $shard_id]) ?: false;
            if ($shard_result) {
                $shard_result['server'] = $this->getServerById($shard_result['database_server_id']);
            }
            $this->shard_cache[$shard_id] = $shard_result;
        }
        return $this->shard_cache[$shard_id];
    }

    public function getServerById($server_id) {
        if (!isset($this->server_cache[$server_id])) {
            $server_result = $this->getServerIndex()->find('==', ['database_server_id' => $server_id]) ?: false;
            if ($server_result) {
                $server_result['ip_address'] = long2ip($server_result['ip_address']);
            }
            $this->server_cache[$shard_result['database_server_id']] = $server_result;
        }
        return $this->server_cache[$shard_result['database_server_id']];
    }

    protected function getShardIndex() {
        if (null === $this->HsShardIndex) {
            $this->HsShardIndex = $this->getHsConn()->useDb($this->shard_database)->openIndex(
                $this->shard_table_name,
                'PRIMARY',
                ['database_shard_id', 'database_server_id']
            );
        }
        return $this->HsShardIndex;
    }

    protected function getServerIndex() {
        if (null === $this->HsServerIndex) {
            $this->HsServerIndex = $this->getHsConn()->useDb($this->shard_database)->openIndex(
                $this->servers_table_name,
                'PRIMARY',
                ['database_server_id', 'ip_address', 'port', 'username', 'password', 'super_username', 'super_password']
            );
        }
        return $this->HsServerIndex;
    }

    protected function getHsConn() {
        return $this->Manager->getCentralHsConnection();
    }

}
