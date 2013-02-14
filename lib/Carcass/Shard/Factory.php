<?php

namespace Carcass\Shard;

use Carcass\Config;
use Carcass\Application\Injector;

class Factory {

    protected
        $Config,
        $model_class_prefix = null,
        $model_class_namespace = null,
        $registry = [];

    public function __construct(Config\ItemInterface $ShardingConfig = null) {
        $this->Config = $ShardingConfig ?: $this->getApplicationShardingConfig();
    }

    public function setModelClassPrefix($model_class_prefix = null) {
        $this->model_class_prefix = $model_class_prefix ?: null;
        return $this;
    }

    public function setModelClassNamespace($model_class_namespace = null) {
        $this->model_class_namespace = $model_class_namespace ?: null;
        return $this;
    }

    public function getModel($class_name, UnitInterface $Unit) {
        $fq_class_name = $this->getModelFqClassName($class_name);
        return new $fq_class_name($Unit, $this);
    }

    public function getModelFqClassName($class_name) {
        return Corelib\ObjectTools::resolveRelativeClassName(
            $class_name,
            $this->model_class_prefix,
            $this->model_class_namespace ?: Injector::getNamespace()
        );
    }

    public function getAllocator($connection_type) {
        return $this->getItemByType('allocators', $connection_type);
    }

    public function getMapper($connection_type) {
        return $this->getItemByType('mappers', $connection_type);
    }

    protected function getItemByType($category, $connection_type) {
        if (!isset($this->registry[$category][$connection_type])) {
            $this->registry[$category][$connection_type] = $this->assembleItemByType($category, $connection_type);
        }
        return $this->registry[$category][$connection_type];
    }

    protected function assembleItemByType($category, $connection_type) {
        $config_fn = $this->Config->getPath("{$category}.${connection_type}");
        if (!is_callable($config_fn)) {
            throw new \LogicException("No function configured for '$category'.'$connection_type'");
        }
        return $config_fn(Injector::getInstance(), $this->Config);
    }

    protected function getApplicationShardingConfig() {
        return Injector::getConfigReader()->sharding;
    }

}
