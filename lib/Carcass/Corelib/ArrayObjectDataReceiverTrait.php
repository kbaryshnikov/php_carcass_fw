<?php

namespace Carcass\Corelib;

trait ArrayObjectDataReceiverTrait {

    protected function setArrayObjectItemByKey($key, $value) {
        $this->set($key, $value);
    }

    protected function unsetArrayObjectItemByKey($key) {
        $this->delete($key);
    }
}
