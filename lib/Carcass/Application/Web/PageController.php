<?php

namespace Carcass\Application;

use Carcass\Corelib;

abstract class Web_PageController extends Controller {

    protected $Result = null;

    public function dispatch($action, Corelib\Hash $Args) {
        $method = 'action' . $action;
        if (!method_exists($this, $method)) {
            throw new \RuntimeException("Action not implemented: '$action'");
        }
        $this->initBeforeAction();
        return $this->handleActionResult($this->$method($Args));
    }

    protected function initBeforeAction() {
        $this->initResultObject();
    }

    protected function handleActionResult($result) {
        if (is_int($result)) {
            if ($result < 400 || $result >= 600) {
                throw new \InvalidArgumentException('error status must be in range [400, 600)');
            }
            return $this->getRenderer()->setStatus($result);
        }
        return $result;
    }

    protected function getRenderer(Corelib\ResultInterface $Result = null, $template_file = null) {
        $Renderer = $this->assembleRenderer($template_file);
        $Result and $Renderer->set($Result);
        return $Renderer;
    }

    protected function assembleRenderer($template_file = null) {
        $RendererCfg = Injector::getConfigReader()->web->renderer;
        $class_name = Corelib\ObjectTools::resolveRelativeClassName($RendererCfg->class, '\Carcass\Application\Web_Renderer_');
        return new $class_name($RendererCfg->exportArrayFrom('args'), $template_file);
    }

    protected function initResultObject() {
        $this->Result = new Corelib\Result;
    }

    protected function redirectToRoute($route, array $args = [], $status = 302) {
        $url = $this->getRouter()->getAbsoluteUrl($route, $args);
        return $this->redirectToUrl($url, $status);
    }

    protected function redirectToUrl($url, $status = 302) {
        return new Web_Renderer_Redirect($url, $status);
    }

    protected function sendFile($location) {
        return new Web_Renderer_Sendfile($location);
    }

}
