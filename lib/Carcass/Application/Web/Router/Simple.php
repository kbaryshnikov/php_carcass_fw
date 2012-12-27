<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_Router_Simple implements Web_RouterInterface {

    public function route(Corelib\Request $Request, ControllerInterface $Controller) {
        $uri = $Request->Env->has('DOCUMENT_URI') ? $Request->Env->DOCUMENT_URI : strtok($Request->Env->REQUEST_URI, '?');
        $route = $this->findRoute($uri);
        if (!$route) {
            $Controller->dispatchNotFound("Route not found for $uri");
        } else {
            $Controller->dispatch($route, new Corelib\Hash($Request->Args->exportArray()));
        }
    }

    public function getUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->buildUrl($route, $args)->getRelative();
    }

    public function getAbsoluteUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->buildUrl($route, $args)->getAbsolute($Request->Env->HOST, $Request->Env->get('SCHEME', 'http'));
    }

    protected function findRoute($uri) {
        $controller = join('_', array_map('ucfirst', array_filter(explode('/', strtok($uri, '.')), 'strlen'))) ?: 'Default';
        $action = strtok(null);
        return $action ? "{$controller}.{$action}" : $controller;
    }

    protected function buildUrl($route, array $args = []) {
        $controller = strtok($route, '.');
        $action = strtok(null);

        $uri  = '/' . join('/', array_map('lcfirst', array_filter(preg_split('/[\\_]/', $controller), 'strlen')));
        if ($action) {
            $uri .= ".$action";
        }

        return new Corelib\Url($uri, [], $args);
    }

}
