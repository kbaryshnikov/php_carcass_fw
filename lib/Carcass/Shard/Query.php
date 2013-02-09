<?php

namespace Carcass\Shard;

use Carcass\Connection;

class Query extends Carcass\Query\Memcached {

    protected
        $Unit,
        $unit_args,
        $DsnMapperFactory;

    public function __construct(Unit $Unit, DsnMapperFactory $DsnMapperFactory) {
        $this->Unit = $Unit;
        $this->unit_args = [$Unit->getKey() => $Unit->getId()];
        $this->DsnMapperFactory = $DsnMapperFactory;
    }

    protected function getArgs(array $args) {
        return $this->unit_args + $args;
    }

    protected function getDatabaseDsn() {
        return $this->DsnMapperFactory->getMapperByType('mysql')->getDsn($this->Unit);
    }

    protected function getMemcachedDsn() {
        return $this->DsnMapperFactory->getMapperByType('memcached')->getDsn($this->Unit);
    }

}
