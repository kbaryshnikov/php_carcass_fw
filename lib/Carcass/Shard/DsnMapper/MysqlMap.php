<?php

namespace Carcass\Shard;

use Carcass\Config;
use Carcass\Connection;
use Carcass\Mysql;

class DsnMapper_MysqlHs implements DsnMapperInterface {

    const 
        DEFAULT_TABLE_NAME = 'DatabaseShards',
        DEFAULT_INDEX_NAME = 'idx_shard_id';

    protected $HsConn;
    protected $shard_table_name;
    protected $shard_index_name;
    protected $ShardDefaults;
    protected $HsShardIndex = null;
    protected $HsServerIndex = null;
    protected $dsn_cache = [];
    protected $server_cache = [];

    public function __construct(Mysql\HandlerSocket_Connection $HsConn, array $shard_defaults = [], array $opts = []) {
        $this->HsConn = $HsConn;
        $this->shard_table_name = isset($opts['shard_table']) ? $opts['shard_table'] : static::DEFAULT_TABLE_NAME;
        $this->shard_index_name = isset($opts['shard_index']) ? $opts['shard_index'] : static::DEFAULT_INDEX_NAME;
        $this->ShardDefaults    = new Corelib\Hash($shard_defaults);
    }

    public function getDsn(UnitInterface $Unit) {
        $shard_id = $Unit->getShardId();
        if (!isset($this->dsn_cache[$shard_id])) {
            $shard_connection_params = $this->getShardConnectionParams($shard_id);
            if (!$shard_connection_params) {
                throw new \RuntimeException("Shard connection parameters not found for shard id '$shard_id'");
            }
            $this->dsn_cache[$shard_id] = Connection\Dsn::constructByTokens(
                $this->ShardDefaults->deepClone()->merge($shard_connection_params)
            );
        }
        return $this->dsn_cache[$shard_id];
    }

    protected function getShardConnectionParams($shard_id) {
        $shard_result = $this->getShardIndex()->find('=', ['database_shard_id' => $shard_id]);
        if (!$shard_result) {
            return null;
        }
        if (!isset($this->server_cache[$shard_result['database_server_id']])) {
            $server_result = $this->getServerIndex()->find('=', ['database_server_id' => $shard_result['database_server_id']]);
            if (!$server_result) {
                return null;
            }
            $result = [
                'database_shard_id' => $shard_result['database_shard_id'],
                'database_server_id' => $server_result['database_server_id'],
                'hostname' => long2ip($server_result['ip_address']),
                'type' => 'mysqls',
            ];
            foreach (['port', 'username', 'password'] as $key) {
                if (!empty($server_result[$key])) {
                    $result[$key] = $server_result[$key];
                }
            }
            $result['args']['shard_id'] = $shard_result['database_shard_id'];
            $this->server_cache[$shard_result['database_server_id']] = $result;
        }
        return $this->server_cache[$shard_result['database_server_id']];
    }

    protected function getShardIndex() {
        if (null === $this->HsShardIndex) {
            $this->HsShardIndex = $this->HsConn->openIndex(
                $this->shard_table_name,
                $this->shard_index_name,
                ['database_shard_id', 'database_server_id']
            );
        }
        return $this->HsShardIndex;
    }

    protected function getServerIndex() {
        if (null === $this->HsServerIndex) {
            $this->HsServerIndex = $this->HsConn->openIndex(
                $this->server_table_name,
                $this->server_index_name,
                ['database_server_id', 'ip_address', 'port', 'username', 'password']
            );
        }
        return $this->HsShardIndex;
    }

}
