<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Corelib;
use Carcass\Application\Injector;
use Carcass\Mysql;

/**
 * Mysql ShardingModel
 * Manages shards and servers storage in the central sharding database (mysql/handlerSocket).
 * Allocates shards for Units.
 *
 * @package Carcass\Shard
 */
class Mysql_ShardingModel {

    /**
     * @var Mysql_ShardManager
     */
    protected $Manager;

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @param Mysql_ShardManager $Manager
     */
    public function __construct(Mysql_ShardManager $Manager) {
        $this->Manager = $Manager;
    }

    /**
     * Allocates shard for $Unit. Tries to find the best shard, or if there's no shard
     * available, creates a new shard. Unit which received a new shard will be asked
     * to initialize its tables by calling $Unit->initializeShard().
     *
     * @param UnitInterface $Unit
     */
    public function allocateShard(UnitInterface $Unit) {
        $Shard = $this->findBestShardForNewUnit();
        if ($Shard) {
            $Unit->setShard($Shard);
        } else {
            $this->allocateNewShard($Unit);
        }
    }

    /**
     * @return ShardInterface|null
     */
    protected function findBestShardForNewUnit() {
        $shard_id = $this->getDb()->getCell(
            "SELECT
                DSH.database_shard_id
            FROM
                DatabaseShards DSH
            WHERE
                DSH.is_available = TRUE
                AND DSH.units_free > 0
            ORDER BY
                DSH.units_free DESC
            LIMIT 1"
        );
        return $shard_id ? $this->getShardById($shard_id) : null;
    }

    /**
     * @param UnitInterface $Unit
     * @return ShardInterface
     * @throws \RuntimeException
     * @throws \LogicException
     */
    protected function allocateNewShard(UnitInterface $Unit) {
        $shard_id = $this->doWithLockedTables(function() {
            $Db = $this->getDb();
            $server_id = $Db->getCell(
                "SELECT
                    SRV.database_server_id,
                FROM
                    DatabaseServers SRV
                LEFT JOIN
                    DatabaseShards DSH ON (
                        DSH.database_server_id = SRV.database_server_id
                    )
                WHERE
                    SRV.is_available = TRUE
                GROUP BY
                    SRV.database_server_id
                ORDER BY
                    COUNT(DSH.database_shard_id) / SRV.capacity
                LIMIT 1"
            );
            if (!$server) {
                throw new \RuntimeException("No server available for new shard was found");
            }

            $Server = $this->getServerById($server_id);

            if ($Server->databases_count < 1) {
                throw new \LogicException('databases_count is less than 1 for server #' . $server_id);
            }

            $db_idx_series = [];
            foreach (range(1, $Server->databases_count) as $idx) {
                $db_idx_series['idx'] = $idx;
            }
            if (empty($db_idx_series['idx'])) {
                throw new \LogicException("No database indexes allocated on server " . $server_id);
            }

            $db_index_data = $Db->getCell(
                "SELECT
                    SEQ.idx,
                    COUNT(database_shard_id) AS shards_count
                FROM
                    (
                        {{ BEGIN SERIES }}
                            SELECT {{ idx }} AS idx {{ UNLESS _last }} UNION {{ END }}
                        {{ END }}
                    ) AS SEQ
                LEFT JOIN
                    DatabaseShards ON (database_idx = SEQ.idx AND database_server_id = {{ i(database_server_id) }})
                GROUP BY
                    SEQ.idx
                ORDER BY
                    COUNT(database_shard_id), SEQ.idx",
                [ 
                    'database_server_id' => $server_id,
                    'SERIES' => $db_idx_series,
                ]
            );
            if (!$db_index_data) {
                throw new \RuntimeException("Could not fetch db index data for server #" . $server['database_server_id']);
            }

            $db_name = $this->Manager->getShardDbNameByIndex($db_index_data['idx']);

            if ($db_index_data['shards_count'] == 0) {
                $SuperDb = new Mysql\Client(Injector::getConnectionManager()->getConnection($Server->getSuperDsn()));
                $SuperDb->query("CREATE DATABASE IF NOT EXISTS {{ name(db_name) }}", compact('db_name'));
                $SuperDb->query("GRANT ALL PRIVILEGES ON {{ name(db_name) }} TO {{ s(username) }}@'%' IDENTIFIED BY {{ s(password) }}", [
                    'db_name'   => $db_name,
                    'username'  => $Server->username,
                    'password'  => $Server->password,
                ]);
            }

            $Db->query(
                "INSERT INTO
                    DatabaseShards
                SET
                    database_server_id = {{ i(database_server_id) }},
                    database_idx = {{ i(database_idx) }},
                    units_allocated = 0,
                    units_free = {{ i(units_free) }},
                    created_ts = NOW()",
                [
                    'database_server_id' => $server_id,
                    'database_idx' => $db_index_data['idx'],
                    'units_free' => $Server->units_per_shard,
                ]
            );

            return $Db->getLastInsertId();
        });

        $Shard = $this->getShardById($shard_id);
        $Unit->setShard($Shard);

        $Unit->initializeShard();

        return $Shard;
    }

    /**
     * @param int $shard_id
     * @param bool $skip_cache
     * @return Mysql_Shard
     * @throws \RuntimeException
     */
    public function getShardById($shard_id, $skip_cache = false) {
        Corelib\Assert::that('shard_id is a valid ID', '\InvalidArgumentException')->isValidId($shard_id);
        if ($skip_cache || !isset($this->cache['shards'][$shard_id])) {
            $shard_data = $this->fetchServerFromHsById($shard_id);
            if (!$shard_data) {
                throw new \RuntimeException("Shard with id '$shard_id' not found");
            }
            $this->cache['shards'][$shard_id] = new Mysql_Shard($this->Manager, $shard_data);
        }
        return $this->cache['shards'][$shard_id];
    }

    /**
     * @param int $server_id
     * @param bool $skip_cache
     * @return Mysql_Server
     * @throws \RuntimeException
     */
    public function getServerById($server_id, $skip_cache = false) {
        Corelib\Assert::that('server_id is a valid ID', '\InvalidArgumentException')->isValidId($server_id);
        if ($skip_cache || !isset($this->cache['servers'][$server_id])) {
            $server_data = $this->fetchServerFromHsById($server_id);
            if (!$server_data) {
                throw new \RuntimeException("Server with id '$server_id' not found");
            }
            $this->cache['servers'][$server_id] = new Mysql_Server($server_data);
        }
        return $this->cache['servers'][$server_id];
    }

    /**
     * @param Mysql_Server $Server
     * @return Mysql_Server
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function addServer(Mysql_Server $Server) {
        if (!$Server->get('ip_address')) {
            throw new \InvalidArgumentException('Server has no ip address');
        }
        if (null === $Server->get('is_available')) {
            $Server->is_available = true;
        }
        $ServerDefaultsConfig = $this->Manager->getConfig()->get('server_defaults');
        foreach (['port', 'username', 'password', 'super_username', 'super_password', 'capacity', 'units_per_shard'] as $key) {
            if (null === $Server->get($key)) {
                if ($ServerDefaultsConfig && $ServerDefaultsConfig->has($key)) {
                    $Server->set($key, $ServerDefaultsConfig->get($key));
                }
            }
        }
        $result = $this->getDb()->query(
            "INSERT INTO
                DatabaseServers
            SET
                ip_address = INET_ATON({{ s(ip_address) }}),
                {{ IF port }} port = {{ i(port) }}, {{ END }}
                {{ IF username }} username = {{ s(username) }}, {{ END }}
                {{ IF password }} password = {{ s(password) }}, {{ END }}
                {{ IF super_username }} super_username = {{ s(super_username) }}, {{ END }}
                {{ IF super_password }} super_password = {{ s(super_password) }}, {{ END }}
                {{ IF capacity }} capacity = {{ i(capacity) }}, {{ END }}
                {{ IF units_per_shard }} units_per_shard = {{ i(units_per_shard) }}, {{ END }}
                {{ IF databases_count }} databases_count = {{ i(databases_count) }}, {{ END }}
                is_available = {{ b(is_available) }},
                created_ts = NOW()",
            $Server->exportArray()
        );
        if (!$result) {
            throw new \LogicException('Failed to add server to the database');
        }
        $server_id = $this->getDb()->getLastInsertId();
        return $this->getServerById($server_id, true);
    }

    /**
     * @param $server_id
     * @return null
     */
    protected function fetchServerFromHsById($server_id) {
        $server_result = $this->getServerIndex()->find('==', ['database_server_id' => $server_id]) ?: null;
        if ($server_result) {
            $server_result['ip_address'] = long2ip($server_result['ip_address']);
        }
        return $server_result;
    }

    /**
     * @return mixed
     */
    protected function getServerIndex() {
        if (!isset($this->cache['HsServerIndex'])) {
            $this->cache['HsServerIndex'] = $this->getHsConn()->openIndex(
                'DatabaseServers',
                'PRIMARY',
                ['database_server_id', 'ip_address', 'port', 'username', 'password', 'super_username', 'super_password']
            );
        }
        return $this->cache['HsServerIndex'];
    }

    /**
     * @return \Carcass\Mysql\Client
     */
    protected function getDb() {
        return $this->Manager->getShardingDb();
    }

    /**
     * @return \Carcass\Mysql\HandlerSocket_Connection
     */
    protected function getHsConn() {
        return $this->Manager->getShardingHsConnection();
    }

    /**
     * @param callable $fn
     * @param array $args
     * @return mixed
     * @throws \Exception|null
     */
    protected function doWithLockedTables(Callable $fn, array $args = []) {
        $e = null;
        $this->getDb()->query("LOCK TABLES DatabaseServers, DatabaseShards READ LOCAL");
        try {
            $result = call_user_func_array($fn, $args);
        } catch (\Exception $e) {
            // pass
        }
        // finally:
        $this->getDb()->query("UNLOCK TABLES");
        if ($e) {
            throw $e;
        }
        return $result;
    }

    /**
     * @param bool $drop_existing_tables
     * @return $this
     */
    public function initializeShardingDatabase($drop_existing_tables = false) {
        $Db = $this->getDb();
        if ($drop_existing_tables) {
            $Db->query('DROP TABLE IF EXISTS DatabaseShards');
            $Db->query('DROP TABLE IF EXISTS DatabaseServers');
        }
        $Db->query(
            "CREATE TABLE DatabaseServers (
                database_server_id int(10) unsigned NOT NULL AUTO_INCREMENT,
                ip_address int(10) unsigned NOT NULL,
                port smallint(5) unsigned NOT NULL DEFAULT '3306',
                username varchar(32) NOT NULL DEFAULT '',
                password varchar(32) NOT NULL DEFAULT '',
                super_username varchar(32) NOT NULL DEFAULT 'root',
                super_password varchar(32) NOT NULL DEFAULT '',
                capacity int(10) unsigned NOT NULL DEFAULT '100' COMMENT 'Relative, measured in parrots',
                units_per_shard int(10) unsigned NOT NULL DEFAULT '1000',
                databases_count tinyint unsigned NOT NULL DEFAULT '10',
                is_available boolean NOT NULL DEFAULT TRUE,
                created_ts timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
                updated_ts timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (database_server_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $Db->query(
            "CREATE TABLE DatabaseShards (
                database_shard_id int(10) unsigned NOT NULL AUTO_INCREMENT,
                database_server_id int(10) unsigned NOT NULL,
                database_idx tinyint unsigned NOT NULL,
                units_allocated int(10) unsigned NOT NULL DEFAULT '0',
                units_free int(10) unsigned NOT NULL,
                is_available boolean NOT NULL DEFAULT TRUE,
                created_ts timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
                updated_ts timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (database_shard_id),
                KEY idx_database_server_id (database_server_id),
                KEY idx_units_free_database_server_id (units_free, database_server_id),
                CONSTRAINT FOREIGN KEY (database_server_id) REFERENCES DatabaseServers (database_server_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        return $this;
    }
}
