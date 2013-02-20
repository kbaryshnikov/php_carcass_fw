<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;

/**
 * Simple router. Directly maps document URI to route name
 * by replacing '/' to '_' and uppercasing tokens, e.g.:
 *    /foo/bar     = Foo_Bar (default action)
 *    /foo/bar.act = Foo_Bar.Act.
 *
 * @package Carcass\Application
 */
class Web_Router_Simple implements Web_Router_Interface {
    use Web_Router_StaticTrait;

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param ControllerInterface $Controller
     */
    public function route(Corelib\Request $Request, ControllerInterface $Controller) {
        $uri   = $Request->Env->has('DOCUMENT_URI') ? $Request->Env->DOCUMENT_URI : strtok($Request->Env->REQUEST_URI, '?');
        $route = $this->findRoute($uri);
        if (!$route) {
            $Controller->dispatchNotFound("Route not found for $uri");
        } else {
            $Controller->dispatch($route, new Corelib\Hash($Request->Args->exportArray()));
        }
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param $route
     * @param array $args
     * @return null
     */
    public function getUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->buildUrl($route, $args)->getRelative();
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param $route
     * @param array $args
     * @return string
     */
    public function getAbsoluteUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->buildUrl($route, $args)->getAbsolute($Request->Env->HOST, $Request->Env->get('SCHEME', 'http'));
    }

    /**
     * @param $uri
     * @return string
     */
    protected function findRoute($uri) {
        $controller = join('_', array_map('ucfirst', array_filter(explode('/', strtok($uri, '.')), 'strlen'))) ? : 'Default';
        $action     = strtok(null);
        return $action ? "{$controller}.{$action}" : $controller;
    }

    /**
     * @param $route
     * @param array $args
     * @return \Carcass\Corelib\Url
     */
    protected function buildUrl($route, array $args = []) {
        $controller = strtok($route, '.');
        $action     = strtok(null);

        $uri = '/' . join('/', array_map('lcfirst', array_filter(preg_split('/[\\_]/', $controller), 'strlen')));
        if ($action) {
            $uri .= ".$action";
        }

        return new Corelib\Url($uri, [], $args);
    }

}
