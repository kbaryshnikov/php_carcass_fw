<?php

namespace Carcass\Application;

use Carcass\Config as Config;

class Web_Router_Factory {
    
    public static function assembleByConfig(Config\Item $RouteConfig) {
        switch ($RouteConfig->name) {
            case 'map':
                return static::assembleMapRouter($RouteConfig);
            default:
                throw new \LogicException("Unknown router name: '{$RouteConfig->name}'");
        }
    }

    public static function assembleMapRouter(Config\Item $Config) {
        return new Web_Router_Map($Config->exportArrayFrom('map', []), $Config->exportArrayFrom('args', []));
    }

}
