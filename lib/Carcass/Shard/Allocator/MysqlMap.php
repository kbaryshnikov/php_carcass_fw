<?php

namespace Carcass\Shard;

use Carcass\Connection;
use Carcass\Mysql;
use Carcass\Config;
use Carcass\Database;
use Carcass\Corelib;

class Allocator_MysqlMap implements AllocatorInterface {

    const
        DEFAULT_SHARDING_DBNAME = 'Sharding';

    protected
        $db_name,
        $Db;

    public function __construct(Mysql\Connection $DbConnection, $db_name = null) {
        $this->Db = Database\Factory::assemble($DbConnection);
        $this->db_name = $db_name ?: static::DEFAULT_SHARDING_DBNAME;
    }

    public function allocate(UnitInterface $Unit) {
        return $this->Db->doInTransaction(function() use ($Unit) {
            $is_new_shard = false;
            $shard_id = $this->getMostFreeShard();
            if (!$shard_id) {
                $shard_id = $this->allocateNewShard();
                $is_new_shard = true;
            }
            if (!$shard_id) {
                throw new \RuntimeException('Failed to allocate shard');
            }
            $this->allocateUnitInShard($shard_id);
            $Unit->setShardId($shard_id);
            return $is_new_shard;
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
        $least_busy_server = $this->Db->getRow(
            "SELECT
                SRV.database_server_id,
                SRV.units_per_shard
            FROM
                {{ name(table_DatabaseServers) }} SRV
            WHERE
                is_available = TRUE
                AND NOT EXISTS (
                    SELECT 1 FROM {{ name(table_DatabaseShards) }} DSH
                    WHERE DSH.database_server_id = SRV.database_server_id
                )
            ORDER BY SRV.capacity DESC
            LIMIT 1",
            [
                'table_DatabaseServers' => $this->getFqTable('DatabaseServers'),
                'table_DatabaseShards'  => $this->getFqTable('DatabaseShards'),
            ]
        );
        if (!$least_busy_server) {
            $least_busy_server = $this->Db->getRow(
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
        }
        if (!$least_busy_server) {
            throw new \LogicException("Could not find a server");
        }
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
                'units_per_shard'       => $least_busy_server['units_per_shard'],
            ]
        );
        if (!$result) {
            throw new \LogicException('Adding a shard failed');
        }
        return $this->Db->getLastInsertId();
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

    public function addServer(/* ArrayAccess */$server, UnitInterface $Unit) {
        if (!Corelib\ArrayTools::isArrayAccessable($server)) {
            throw new \InvalidArgumentException('Argument must be array or ArrayAccess instance');
        }
        $args = [
            'table_DatabaseServers' => $this->getFqTable('DatabaseServers'),
            'ip_address' => $server['ip_address'],
            'is_available' => isset($server['is_available']) ? (bool)$server['is_available'] : true,
        ];
        foreach (['port', 'username', 'password', 'capacity', 'units_per_shard'] as $key) {
            if (isset($server[$key])) {
                $args[$key] = $server[$key];
            } else {
                $args[$key] = null;
            }
        }
        $result = $this->Db->query(
            "INSERT INTO
                {{ name(table_DatabaseServers) }}
            SET
                ip_address = INET_ATON({{ s(ip_address) }}),
                {{ IF port }} port = {{ i(port) }}, {{ END }}
                {{ IF username }} username = {{ s(username) }}, {{ END }}
                {{ IF password }} password = {{ s(password) }}, {{ END }}
                {{ IF capacity }} capacity = {{ i(capacity) }}, {{ END }}
                {{ IF units_per_shard }} units_per_shard = {{ i(units_per_shard) }}, {{ END }}
                is_available = {{ b(is_available) }},
                created_ts = NOW()",
            $args
        );
        if (!$result) {
            throw new \LogicException('Failed to add server to the database');
        }
        $id = $this->Db->getLastInsertId();
        $NewServerConn = Database\Factory::assemble(new Mysql\Connection(Connection\Dsn::constructByTokens(new Corelib\Hash([
            'scheme'    => 'mysql',
            'host'      => $args['ip_address'],
            'port'      => $args['port'],
            'user'      => isset($server['management_username']) ? $server['management_username'] : 'root',
            'pass'      => isset($server['management_password']) ? $server['management_password'] : '',
        ]))));
        if (!empty($server['drop_database_if_exists'])) {
            $NewServerConn->query("DROP DATABASE IF EXISTS {{ name(database) }}", ['database' => $Unit->getDatabaseName()]);
        }
        $NewServerConn->query("CREATE DATABASE IF NOT EXISTS {{ name(database) }}", ['database' => $Unit->getDatabaseName()]);
        $NewServerConn->query("
            GRANT ALL PRIVILEGES ON {{ name(database) }}.*
            TO {{ s(user) }}@{{ s(user_host) }}
            IDENTIFIED BY {{ s(password) }}
        ", [
            'database' => $Unit->getDatabaseName(),
            'user'     => $args['username'] ?: '',
            'password' => $args['password'] ?: '',
            'user_host'=> isset($server['user_host']) ? $server['user_host'] : '%',
        ]);
        return $id;
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
