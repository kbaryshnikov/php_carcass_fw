<?php

namespace Carcass\Corelib;

use Closure;
use InvalidArgumentException;

class Injector {

    protected $registry = [];

    public function reuse(Closure $ctor) {
        return function($self) use ($ctor) {
            static $instance = null;
            return null === $instance ? $instance = $ctor($self) : $instance;
        };
    }

    public function setClosure($name, Closure $value) {
        $this->$name = function() use ($value) {
            return $value;
        };
    }

    public function __set($name, $value) {
        $this->registry[$name] = $value;
    }

    public function __get($name) {
        if (!array_key_exists($name, $this->registry)) {
            throw new InvalidArgumentException("Undefined dependency: '$name'");
        }
        return $this->registry[$name] instanceof Closure ? $this->registry[$name]($this) : $this->registry[$name];
    }

    public function __call($name, array $arguments) {
        if (!array_key_exists($name, $this->registry)) {
            throw new InvalidArgumentException("Undefined dependency: '$name'");
        }
        if (!$this->registry[$name] instanceof Closure) {
            throw new InvalidArgumentException("Dependency is not constructable: '$name'");
        }
        array_unshift($arguments, $this);
        return call_user_func_array($this->registry[$name], $arguments);
    }

}
