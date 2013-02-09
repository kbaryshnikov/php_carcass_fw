<?php

namespace Carcass\Shard;

use Carcass\Model;

class Model extends Model\Memcached {

    protected
        $DsnMapper,
        $Unit;

    public function __construct(DsnMapper $DsnMapper, UnitInterface $Unit) {
        $this->Unit = $Unit;
        parent::__construct();
    }

    protected function createQueryInstance() {
        return parent::createQueryInstance()->setOptions(['prefix' => $this->getMemcachedKeysPrefix()]);
    }

    protected function getMemcachedKeysPrefix() {
        return sprintf('%s=%d:', $this->Unit->getKey(), $this->Unit->getId());
    }

}
