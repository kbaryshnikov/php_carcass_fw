<?php

namespace Carcass\Shard;

use Carcass\Config;
use Carcass\Connection;
use Carcass\Mysql;
use Carcass\Corelib;

class DsnMapper_MysqlMap implements DsnMapperInterface {

    const
        DEFAULT_TABLE_NAME = 'DatabaseShards',
        DEFAULT_SERVERS_TABLE_NAME = 'DatabaseServers';

    protected $HsConn;
    protected $shard_database;
    protected $shard_table_name;
    protected $servers_table_name;
    protected $ShardDefaults;
    protected $HsShardIndex = null;
    protected $HsServerIndex = null;
    protected $dsn_cache = [];
    protected $server_cache = [];

    public function __construct(Mysql\HandlerSocket_Connection $HsConn, array $shard_defaults = [], array $opts = []) {
        $this->HsConn = $HsConn;
        $this->shard_database = isset($opts['shard_database']) ? $opts['shard_database'] : Allocator_MysqlMap::DEFAULT_SHARDING_DBNAME;
        $this->shard_table_name = isset($opts['shard_table']) ? $opts['shard_table'] : static::DEFAULT_TABLE_NAME;
        $this->servers_table_name = isset($opts['servers_table']) ? $opts['servers_table'] : static::DEFAULT_SERVERS_TABLE_NAME;
        $this->ShardDefaults = new Corelib\Hash($shard_defaults);
    }

    public function getDsn(UnitInterface $Unit) {
        $shard_id = $Unit->getShardId();
        if (!$shard_id) {
            throw new \LogicException("Unit has no shard id defined");
        }
        if (!isset($this->dsn_cache[$shard_id])) {
            $shard_connection_params = $this->getShardConnectionParams($shard_id);
            if (!$shard_connection_params) {
                throw new \RuntimeException("Shard connection parameters not found for shard id '$shard_id'");
            }
            $Tokens = clone $this->ShardDefaults;
            $Tokens->merge($shard_connection_params);
            $this->dsn_cache[$shard_id] = Connection\Dsn::constructByTokens($Tokens);
        }
        return $this->dsn_cache[$shard_id];
    }

    protected function getShardConnectionParams($shard_id) {
        $shard_result = $this->getShardIndex()->find('==', ['database_shard_id' => $shard_id]);
        if (!$shard_result) {
            return null;
        }
        if (!isset($this->server_cache[$shard_result['database_server_id']])) {
            $server_result = $this->getServerIndex()->find('==', ['database_server_id' => $shard_result['database_server_id']]);
            if (!$server_result) {
                return null;
            }
            $result = [
                'host' => long2ip($server_result['ip_address']),
                'scheme' => 'mysqls',
            ];
            foreach (['port' => 'port', 'username' => 'user', 'password' => 'pass'] as $key => $result_key) {
                if (!empty($server_result[$key])) {
                    $result[$result_key] = $server_result[$key];
                }
            }
            $this->server_cache[$shard_result['database_server_id']] = $result;
        }
        return ['query' => http_build_query(['shard_id' => $shard_id])] + $this->server_cache[$shard_result['database_server_id']];
    }

    protected function getShardIndex() {
        if (null === $this->HsShardIndex) {
            $this->HsShardIndex = $this->HsConn->useDb($this->shard_database)->openIndex(
                $this->shard_table_name,
                'PRIMARY',
                ['database_shard_id', 'database_server_id']
            );
        }
        return $this->HsShardIndex;
    }

    protected function getServerIndex() {
        if (null === $this->HsServerIndex) {
            $this->HsServerIndex = $this->HsConn->useDb($this->shard_database)->openIndex(
                $this->servers_table_name,
                'PRIMARY',
                ['database_server_id', 'ip_address', 'port', 'username', 'password']
            );
        }
        return $this->HsServerIndex;
    }

}
