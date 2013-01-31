<?php

namespace Carcass\Corelib;

class ObjectTools {

    public static function toString($object) {
        return spl_object_hash($object);
    }

    public static function construct($class_name, array $ctor_args) {
        return (new \ReflectionClass($class_name))->newInstanceArgs($ctor_args);
    }

    public static function resolveRelativeClassName($name, $_prefix = false, $namespace = false) {
        if ($_prefix && substr($name, 0, 1) === '_') {
            $name = $_prefix . substr($name, 1);
        }
        if ($namespace && substr($name, 0, 1) !== '\\') {
            $name = $namespace . '\\' . $name;
        }
        return $name;
    }

}
