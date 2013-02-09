<?php

namespace Carcass\Corelib;

class UniqueId {

    public static function generate($prefix = '') {
        return uniqid($prefix, true);
    }

}
