<?php

namespace Carcass\Shard;

use Carcass\Config;
use Carcass\Application\Injector;

class DsnMapperFactory {

    protected $Config;

    protected $registry = [];

    public function __construct(Config\ItemInterface $ShardingConfig = null) {
        $this->Config = $ShardingConfig ?: Injector::getConfigReader()->sharding;
    }

    public function getMapperByType($connection_type) {
        if (!isset($this->registry[$connection_type])) {
            $this->registry[$connection_type] = $this->assembleMapperByType($connection_type);
        }
        return $this->registry[$connection_type];
    }

    protected function assembleMapperByType($connection_type) {
        $config_fn = $this->Config->mappers->get($connection_type);
        if (!is_callable($config_fn)) {
            throw new \LogicException("No mapper function configured for '$connection_type'");
        }
        return $config_fn(Injector::getInstance());
    }

}
