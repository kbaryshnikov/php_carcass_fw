<?php

namespace Carcass\Shard;

class Model extends \Carcass\Model\Memcached {

    protected
        $ShardFactory,
        $Unit;

    public function __construct(UnitInterface $Unit, Factory $ShardFactory) {
        $this->Unit = $Unit;
        $this->ShardFactory = $ShardFactory;
        parent::__construct();
    }

    protected function createQueryInstance() {
        return new Query($this->Unit, $this->ShardFactory);
    }

}
