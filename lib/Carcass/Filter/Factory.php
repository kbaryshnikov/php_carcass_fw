<?php

namespace Carcass\Filter;

use Carcass\Corelib;

class Factory {

    public static function assemble(array $args) {
        $type = (string)array_shift($args);
        if (!$type) {
            throw new \InvalidArgumentException("Missing filter type");
        }
        $class = substr($type, 0, 1) == '\\' ? $type : __NAMESPACE__ . '\\' . ucfirst($type);
        return Corelib\ObjectTools::construct($class, $args);
    }

}
