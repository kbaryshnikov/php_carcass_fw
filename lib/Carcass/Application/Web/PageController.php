<?php

namespace Carcass\Application;

class Web_PageController extends Controller {

    public function dispatch($action, Corelib\Hash $Args) {
        $method = 'action' . $action;
        if (!method_exists($this, $method)) {
            throw new \RuntimeException("Action not implemented: '$action'");
        }
        $View = $this->$method($Args);
        if (is_int($View)) {
            $View = $this->createErrorView($View);
        }
        $View->displayTo($this->Response);
    }

    protected function createErrorView($status) {
        if ($status < 400 || $status >= 600) {
            throw new \InvalidArgumentException('error status must be in range [400, 600)');
        }
        return new Web_View_HttpError($status);
    }

    protected function createView() {
        return new Web_View;
    }

    protected function createRedirectView($route, array $args = []) {
        return new $this->createRawRedirectView($this->getRouter()->getAbsoluteUrl($route, $args));
    }

    protected function createRawRedirectView($url) {
        return new Web_View_Redirect($url);
    }

}
