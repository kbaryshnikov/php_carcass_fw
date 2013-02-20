<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Collection of object tools
 *
 * @package Carcass\Corelib
 */
class ObjectTools {

    /**
     * @param $object
     * @return string
     */
    public static function toString($object) {
        return spl_object_hash($object);
    }

    /**
     * @param string $class_name
     * @param array $ctor_args
     * @return object instance of $class_name
     */
    public static function construct($class_name, $ctor_args) {
        return (new \ReflectionClass($class_name))->newInstanceArgs((array)$ctor_args);
    }

    /**
     * @param string $name
     * @param string|null $_prefix
     * @param string|null $namespace
     * @return string
     */
    public static function resolveRelativeClassName($name, $_prefix = null, $namespace = null) {
        if ($_prefix && substr($name, 0, 1) === '_') {
            $name = $_prefix . substr($name, 1);
        }
        if ($namespace && substr($name, 0, 1) !== '\\') {
            $name = rtrim($namespace, '\\') . '\\' . $name;
        }
        if (substr($name, 0, 1) != '\\') {
            $name = '\\' . $name;
        }
        return $name;
    }

}
