<?php

namespace Carcass\Shard;

use Carcass\Connection;
use Carcass\Mysql;
use Carcass\Config;
use Carcass\Database;

class Allocator_MysqlMap implements AllocatorInterface {

    const DEFAULT_SHARDING_DBNAME = 'Sharding';

    protected
        $db_name,
        $Db;

    public function __construct(Mysql\Connection $DbConnection, Mysql\HandlerSocket_Connection $HsConnection, $db_name = null) {
        $this->Db = Database\Factory::assemble($DbConnection);
        $this->db_name = $db_name ?: static::DEFAULT_SHARDING_DBNAME;
    }

    public function allocate(UnitInterface $Unit) {
        return $this->Db->doInTransaction(function() use ($Unit) {
            $shard_id = $this->getMostFreeShard();
            if (!$shard_id) {
                $shard_id = $this->allocateNewShard();
            }
            if (!$shard_id) {
                throw new \RuntimeException('Failed to allocate shard');
            }
            $this->allocateUnitInShard($shard_id);
            $Unit->setShardId($shard_id);
            return $shard_id;
        });
    }

    protected function allocateUnitInShard($shard_id) {
        $result = $this->Db->query(
            "UPDATE
                {{ name(table_DatabaseShards) }}
            SET
                units_allocated = units_allocated + 1,
                units_free = units_free - 1
            WHERE
                database_shard_id = {{ i(shard_id) }}
                AND is_available = TRUE
                AND units_free > 0",
            [
                'table_DatabaseShards'  => $this->getFqTable('DatabaseShards'),
                'shard_id' => $shard_id,
            ]
        );
        if (!$result) {
            throw new \LogicException('Allocating unit in shard #' . $shard_id . ' failed');
        }
    }

    protected function allocateNewShard() {
        $least_busy_server = $this->Db->getCell(
            "SELECT
                SRV.database_server_id,
                SRV.units_per_shard,
                X.c / SRV.capacity AS usage_ratio
            FROM
                {{ name(table_DatabaseServers) }} SRV,
                (
                    SELECT COUNT(database_shard_id) AS c, database_server_id
                    FROM {{ name(table_DatabaseShards) }}
                    GROUP BY database_server_id
                ) X
            WHERE
                SRV.database_server_id = X.database_server_id
                AND SRV.is_available = TRUE
            ORDER BY usage_ratio
            LIMIT 1",
            [
                'table_DatabaseServers' => $this->getFqTable('DatabaseServers'),
                'table_DatabaseShards'  => $this->getFqTable('DatabaseShards'),
            ]
        );
        $result = $this->Db->query(
            "INSERT INTO
                {{ name(table_DatabaseShards) }}
            SET
                database_server_id = {{ i(database_server_id) }},
                units_free = {{ i(units_per_shard) }},
                is_available = TRUE
            ",
            [
                'table_DatabaseShards'  => $this->getFqTable('DatabaseShards'),
                'database_server_id'    => $least_busy_server['database_server_id'],
                'units_free'            => $least_busy_server['units_per_shard'],
            ]
        );
        if (!$result) {
            throw new \LogicException('Adding a shard failed');
        }
        $shard_id = $this->Db->getLastInsertId();
        if (!$shard_id) {
            throw new \LogicException('Getting ID of just inserted shard failed');
        }
        return $shard_id;
    }

    protected function getMostFreeShard() {
        return $this->Db->getCell(
            "SELECT
                DSH.database_shard_id
            FROM
                {{ name(table_DatabaseShards) }} DSH
            WHERE
                DSH.is_available = TRUE
                AND DSH.units_free > 0
            ORDER BY
                DSH.units_free DESC
            LIMIT 1",
            [ 'table_DatabaseShards' => $this->getFqTable('DatabaseShards') ]
        );
    }

    public function initShardingTables() {
        $tables = [];
        foreach (array_reverse(array_keys(static::$tables)) as $table) {
            $fq_table = $this->getFqTable($table);
            $tables['table_' . $table] = $fq_table;
            $this->Db->query('DROP TABLE IF EXISTS {{ name(table) }}', [ 'table' => $fq_table ]);
        }
        foreach (static::$tables as $table => $create_sql) {
            $this->Db->query($create_sql, $tables);
        }
        return $this;
    }

    protected function getFqTable($table) {
        return $this->db_name . '.' . $table;
    }

    protected static $tables = [
        'DatabaseServers' =>
            "CREATE TABLE {{ name(table_DatabaseServers) }} (
                database_server_id int(10) unsigned NOT NULL AUTO_INCREMENT,
                ip_address int(10) unsigned NOT NULL,
                port smallint(5) unsigned NOT NULL DEFAULT '3306',
                username varchar(32) NOT NULL DEFAULT '',
                password varchar(32) NOT NULL DEFAULT '',
                capacity int(10) unsigned NOT NULL DEFAULT '100' COMMENT 'Relative, measured in parrots',
                units_per_shard int(10) unsigned NOT NULL DEFAULT '1000',
                is_available boolean NOT NULL DEFAULT TRUE,
                created_ts timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
                updated_ts timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (database_server_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        'DatabaseShards' =>
            "CREATE TABLE {{ name(table_DatabaseShards) }} (
                database_shard_id int(10) unsigned NOT NULL AUTO_INCREMENT,
                database_server_id int(10) unsigned NOT NULL,
                units_allocated int(10) unsigned NOT NULL DEFAULT '0',
                units_free int(10) unsigned NOT NULL,
                is_available boolean NOT NULL DEFAULT TRUE,
                created_ts timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
                updated_ts timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (database_shard_id),
                KEY idx_database_server_id (database_server_id),
                KEY idx_units_free_database_server_id (units_free, database_server_id),
                CONSTRAINT FOREIGN KEY (database_server_id) REFERENCES {{ name(table_DatabaseServers) }} (database_server_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    ];

}
