<?php

namespace Carcass\Corelib;

class NullObject {

    public function __get($key) {
        return $this;
    }

    public function __set($key, $value) {
        // pass
    }

    public function __call($method, $args) {
        return $this;
    }

}
