<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Injector {

    private static $instance = null;

    private function __construct() {}

    private function __clone() {}

    public static function getInstance() {
        if (null === self::$instance) {
            throw new \LogicException("Instance is undefined");
        }
        return self::$instance;
    }

    public static function setInstance(Corelib\Injector $Injector = null) {
        return self::$instance = $Injector;
    }

    public static function __callStatic($method, array $args) {
        if (substr($method, 0, 3) == 'get') {
            $dep_name = substr($method, 3);
            if (empty($args)) {
                return static::getInstance()->$dep_name;
            } else {
                return call_user_func_array([static::getInstance(), $dep_name], $args);
            }
        }
        throw new \BadMethodCallException("Invalid method call: '$method'");
    }

}
