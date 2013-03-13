<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Config;

/**
 * Web router factory. Used by Application instance during
 * bootstrap phase to assemble the router according to
 * application configuration.
 *
 * @package Carcass\Application
 */
class Web_Router_Factory {

    /**
     * @param \Carcass\Config\Item $RouteConfig
     * @return \Carcass\Application\Web_Router_Interface
     * @throws \LogicException
     */
    public static function assembleByConfig(Config\Item $RouteConfig) {
        switch ($RouteConfig->get('name')) {
            case 'map':
                return static::assembleMapRouter($RouteConfig);
            case 'simple':
                return static::assembleSimpleRouter($RouteConfig);
            case 'jsonrpc':
                return static::assembleJsonRpcRouter($RouteConfig);
            default:
                throw new \LogicException("Unknown router name: '{$RouteConfig->get('name')}'");
        }
    }

    /**
     * @param \Carcass\Config\Item $Config
     * @return \Carcass\Application\Web_Router_Map
     */
    public static function assembleMapRouter(Config\Item $Config) {
        return (new Web_Router_Map($Config->exportArrayFrom('map', []), $Config->exportArrayFrom('args', [])))
            ->setStatic($Config->getPath('static.uri'), $Config->getPath('static.host'), $Config->getPath('static.scheme'));
    }

    /**
     * @param \Carcass\Config\Item $Config
     * @return \Carcass\Application\Web_Router_Simple
     */
    public static function assembleSimpleRouter($Config) {
        return (new Web_Router_Simple)
            ->setStatic($Config->getPath('static.uri'), $Config->getPath('static.host'), $Config->getPath('static.scheme'));
    }

    /**
     * @param \Carcass\Config\Item $Config
     * @return \Carcass\Application\Web_Router_JsonRpc
     */
    public static function assembleJsonRpcRouter($Config) {
        $Router = new Web_Router_JsonRpc($Config->get('api_url', '/'));
        if ($Config->has('api_class_template')) {
            $Router->setApiClassTemplate($Config->get('api_class_template'));
        }
        if ($Config->has('request_body_provider_fn')) {
            $Router->setRequestBodyProvider($Config->get('request_body_provider_fn'));
        }
        return $Router;
    }

}
