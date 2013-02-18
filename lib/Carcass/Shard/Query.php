<?php

namespace Carcass\Shard;

use Carcass\Query\Memcached as MemcachedQuery;

class Query extends MemcachedQuery {

    protected
        $Unit;

    public function __construct(UnitInterface $Unit) {
        $this->Unit = $Unit;
    }

    protected function assembleMct() {
        return parent::assembleMct()->setOptions([
            'prefix' => $this->Unit->getKey() . '_' . $this->Unit->getId() . '|',
        ]);
    }

    protected function assembleDatabaseClient() {
        return $this->Unit->getDatabase();
    }

    protected function assembleMemcachedConnection() {
        return $this->Unit->getMemcachedConnection();
    }

}
