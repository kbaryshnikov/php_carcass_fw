<?php

namespace Carcass\Corelib;

class ObjectTools {

    public static function toString($object) {
        return spl_object_hash($object);
    }

    public static function construct($class_name, $ctor_args) {
        return (new \ReflectionClass($class_name))->newInstanceArgs((array)$ctor_args);
    }

    public static function resolveRelativeClassName($name, $_prefix = false, $namespace = false) {
        if ($_prefix && substr($name, 0, 1) === '_') {
            $name = $_prefix . substr($name, 1);
        }
        if ($namespace && substr($name, 0, 1) !== '\\') {
            $name = $namespace . '\\' . $name;
        }
        if (substr($name, 0, 1) != '\\') {
            $name = '\\' . $name;
        }
        return $name;
    }

}
