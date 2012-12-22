<?php

namespace Carcass\Corelib;

trait DependencyFactoryTrait {

    protected $dependency_classes = [];

    protected function assembleDependency($impl_name, array $args = []) {
        if (!isset($this->dependency_classes[$impl_name])) {
            throw new \RuntimeException("No dependency class configured for '$impl_name'");
        }
        list ($class, $factory_method) = StringTools::split($this->dependency_classes[$impl_name], '::', [null, null]);
        if ($factory_method) {
            return call_user_func_array([$class, $factory_method], $args);
        } else {
            return call_user_func_array([new \ReflectionClass($class), 'newInstance'], $args);
        }
    }

}
