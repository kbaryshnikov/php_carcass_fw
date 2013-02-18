<?php

namespace Carcass\Shard;

use \Carcass\Model\Memcached as MemcachedModel;

class Model extends MemcachedModel {

    protected
        $Unit;

    public function __construct(UnitInterface $Unit) {
        $this->Unit = $Unit;
        parent::__construct();
    }

    protected function createQueryInstance() {
        return new Query($this->Unit);
    }

}
