<?php

namespace Carcass\Corelib;

trait ArrayObjectDatasourceTrait {

    protected function hasArrayObjectItemByKey($key) {
        return $this->has($key);
    }

    protected function &getArrayObjectItemByKey($key) {
        if ($this->has($key)) {
            return $this->getRef($key);
        }
        throw new \OutOfBoundsException("Key is undefined: '$key'");
    }

}
