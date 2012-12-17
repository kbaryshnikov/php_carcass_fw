<?php

namespace Carcass\Corelib;

trait DependencyFactoryTrait {

    protected $dependency_classes = [];

    protected function assembleDependency($name, array $args = []) {
        if (!isset($this->dependency_classes[$name])) {
            throw new \RuntimeException("No dependency class configured for '$name'");
        }
        return call_user_func_array([new \ReflectionClass($this->dependency_classes[$name]), 'newInstance'], $args);
    }

}
