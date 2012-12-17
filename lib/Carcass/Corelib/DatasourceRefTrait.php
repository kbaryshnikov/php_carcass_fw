<?php

namespace Carcass\Corelib;

trait DatasourceRefTrait {
    use DatasourceTrait;

    public function &getRef($key) {
        self::prepareDatasourceKey($key);
        if (!$this->has($key)) {
            throw new \OutOfBoundsException("Missing value for '$key'");
        }
        return $this->getDataArrayPtr()[$key];
    }

}

