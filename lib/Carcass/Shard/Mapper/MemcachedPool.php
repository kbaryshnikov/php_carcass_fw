<?php

namespace Carcass\Shard;

use Carcass\Connection;

class DsnMapper_MemcachedPool implements DsnMapperInterface {

    protected $DsnPool;

    public function __construct(Connection\DsnPool $DsnPool) {
        $this->DsnPool = $DsnPool;
    }

    public function getDsn(UnitInterface $Unit) {
        return $this->DsnPool;
    }

}
