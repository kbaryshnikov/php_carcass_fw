<?php

namespace Carcass\Shard;

use Carcass\Connection;

class Manager extends Connection\Manager {

    protected $MapperFactory = null;

    public function getConnectionByUnit(UnitInterface $Unit, $connection_type) {
        return $this->getConnectionByDsn(
            $this->getMapperFactory()->getMapperByType($connection_type)->getDsn($Unit)
        );
    }

    protected function getMapperFactory() {
        if (null === $this->MapperFactory) {
            $this->MapperFactory = $this->assembleMapperFactory();
        }
        return $this->MapperFactory;
    }

    protected function assembleMapperFactory() {
        return new DsnMapperFactory;
    }

}
