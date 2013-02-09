<?php

namespace Carcass\Shard;

use Carcass\Application\Injector;
use Carcass\Corelib;

class ModelFactory {

    protected
        $DsnMapper,
        $model_class_prefix = null,
        $model_class_namespace = null;

    public function __construct(DsnMapperInterface $DsnMapper, $model_class_prefix = null, $model_class_namespace = null) {
        $this->DsnMapper = $DsnMapper;
        $this->setModelClassPrefix($model_class_prefix);
        $this->setModelClassNamespace($model_class_namespace);
    }

    public function setModelClassPrefix($model_class_prefix = null) {
        $this->model_class_prefix = $model_class_prefix;
        return $this;
    }

    public function setModelClassNamespace($model_class_namespace = null) {
        $this->model_class_namespace = $model_class_namespace ?: Injector::getNamespace();
    }

    public function getModel($class_name, UnitInterface $Unit) {
        $fq_class_name = Corelib\ObjectTools::resolveRelativeClassName($class_name, $this->model_class_prefix, $this->model_class_namespace);
        return new $fq_class_name($this->DsnMapper, $Unit);
    }

}
