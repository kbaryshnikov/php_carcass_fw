<?php

namespace Carcass\Application;

use Carcass\Corelib as Corelib;

abstract class Controller {

    public function __construct(Request $Request, ResponseInterface $Response, RouterInterface $Router) {
        $this->Request = $Request;
        $this->Response = $Response;
        $this->Router = $Router;
        $this->init();
    }

    public function dispatch($action, Corelib\Hash $Args) {
        $method = 'action' . $action;
        if (!method_exists($this, $method)) {
            throw new \RuntimeException("Action not implemented: '$action'");
        }
        return $this->$method($Args);
    }

    public function dispatchNotFound($error_message) {
        throw new \RuntimeException("Route not found: '$error_message'");
    }

    protected function init() {
        // pass
    }

}
