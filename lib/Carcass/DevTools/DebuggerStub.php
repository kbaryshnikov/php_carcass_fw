<?php

namespace Carcass\DevTools;

class DebuggerStub {

    public function isEnabled() {
        return false;
    }

    public function __toString() {
        return "";
    }

    public function __call($method, $arguments) {
        return $this;
    }

}
