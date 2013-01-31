<?php

namespace Carcass\Application;

use Carcass\Config;

class Web_Router_Factory {
    
    public static function assembleByConfig(Config\Item $RouteConfig) {
        switch ($RouteConfig->name) {
            case 'map':
                return static::assembleMapRouter($RouteConfig);
            case 'simple':
                return static::assembleSimpleRouter($RouteConfig);
            default:
                throw new \LogicException("Unknown router name: '{$RouteConfig->name}'");
        }
    }

    public static function assembleMapRouter(Config\Item $Config) {
        return (new Web_Router_Map($Config->exportArrayFrom('map', []), $Config->exportArrayFrom('args', [])))
            ->setStatic($Config->getPath('static.uri'), $Config->getPath('static.host'), $Config->getPath('static.scheme'));
    }

    public static function assembleSimpleRouter() {
        return (new Web_Router_Simple)
            ->setStatic($Config->getPath('static.uri'), $Config->getPath('static.host'), $Config->getPath('static.scheme'));
    }

}
