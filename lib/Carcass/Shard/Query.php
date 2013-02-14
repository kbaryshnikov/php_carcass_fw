<?php

namespace Carcass\Shard;

use Carcass\Connection;

class Query extends Carcass\Query\Memcached {

    protected
        $Unit,
        $ShardFactory;

    public function __construct(Unit $Unit, Factory $ShardFactory) {
        $this->Unit = $Unit;
        $this->DsnMapperFactory = $DsnMapperFactory;
    }

    protected function assembleMct() {
        return parent::assembleMct()->setOptions(['prefix' => $this->getMemcachedKeysPrefix()]);
    }

    protected function getMemcachedKeysPrefix() {
        return sprintf('%s=%d:', $this->Unit->getKey(), $this->Unit->getId());
    }

    protected function assembleDatabaseConnection() {
        return parent::assembleDatabaseConnection()->setShardUnit($this->Unit);
    }

    protected function getDatabaseDsn() {
        return $this->DsnMapperFactory->getMapperByType('mysql')->getDsn($this->Unit);
    }

    protected function getMemcachedDsn() {
        return $this->DsnMapperFactory->getMapperByType('memcached')->getDsn($this->Unit);
    }

}
