<?php

namespace Carcass\Corelib;

class ObjectTools {

    public static function toString($object) {
        return spl_object_hash($object);
    }

}
