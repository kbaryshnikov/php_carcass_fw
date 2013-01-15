<?php

namespace Carcass\Shard;

use Carcass\Connection;
use Carcass\Mysql;
use Carcass\Config;

/*
Tables:

CREATE TABLE `DatabaseShards` (
  `database_shard_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `database_server_id` int(10) unsigned NOT NULL,
  `sites_allocated` int(10) unsigned NOT NULL DEFAULT '0',
  `created_ts` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`database_shard_id`),
  KEY `database_server_id` (`database_server_id`),
  CONSTRAINT FOREIGN KEY (`database_server_id`) REFERENCES `DatabaseServers` (`database_server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `DatabaseServers` (
  `database_server_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` int(10) unsigned NOT NULL,
  `port` smallint(5) unsigned NOT NULL DEFAULT '3306',
  `username` varchar(32) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `capacity` int(10) unsigned NOT NULL DEFAULT '100' COMMENT 'Relative, measured in parrots',
  `sites_per_shard` int(10) unsigned NOT NULL DEFAULT '1000',
  `created_ts` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `updated_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`database_server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

*/

class Allocator_MysqlMap implements AllocatorInterface {

    protected $MapDbConnection;

    public function __construct(Mysql\Connection $MapDbConnection) {
        $this->MapDbConnection = $MapDbConnection;
    }

    public function allocate(UnitInterface $Unit) {
    }

}
