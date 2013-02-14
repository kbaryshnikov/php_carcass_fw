<?php

namespace Carcass\Shard;

use Carcass\Connection;
use Carcass\Query\Memcached as MemcachedQuery;

class Query extends MemcachedQuery {

    protected
        $Unit,
        $ShardFactory;

    public function __construct(UnitInterface $Unit, Factory $ShardFactory) {
        $this->Unit = $Unit;
        $this->ShardFactory = $ShardFactory;
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
        return $this->ShardFactory->getMapper('mysql')->getDsn($this->Unit);
    }

    protected function getMemcachedDsn() {
        return $this->ShardFactory->getMapper('memcached')->getDsn($this->Unit);
    }

}
