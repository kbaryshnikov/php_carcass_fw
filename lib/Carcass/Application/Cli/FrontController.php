<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Cli_FrontController implements ControllerInterface {

    protected
        $Request,
        $Response,
        $Router;

    public function __construct(Corelib\Request $Request, Corelib\Response $Response, RouterInterface $Router) {
        $this->Request = $Request;
        $this->Response = $Response;
        $this->Router = $Router;
    }

    public function run() {
        try {
            $this->getRouter()->route($this->Request, $this);
        } catch (\Exception $e) {
            Injector::getLogger()->logException($e);
            Injector::getDebugger()->dumpException($e);
            $this->getResponse()->setStatus(255);
        }
        exit($this->getResponse()->getStatus());
    }

    public function dispatch($fq_action, Corelib\Hash $Args) {
        list ($controller, $action) = Corelib\StringTools::split($fq_action, '.', [null, 'Default']);

        $script_class = "{$controller}Script";

        try {
            include_once Injector::getPathManager()->getPathToPhpFile('scripts', $script_class);
        } catch (WarningException $e) {
            $this->getResponse()->setStatus(2)->writeErrorLn("Could not load '$script_class' implementation file: " . $e->getMessage());
            return;
        }

        $script_fq_class = Instance::getFqClassName($script_class);

        if (!class_exists($script_fq_class, false)) {
            $this->getResponse()->setStatus(2)->writeErrorLn("No implementation exists for '$script_class'");
            return;
        }

        $Script = new $script_fq_class($this->Request, $this->getResponse(), $this->getRouter());
        $status = $Script->dispatch($action, $Args);

        if ($status) {
            $this->getResponse()->setStatus($status);
        }
    }

    public function dispatchNotFound($error_message) {
        $this->getResponse()->setStatus(1)->writeErrorLn($error_message);
    }

    protected function getResponse() {
        return null !== $this->Response ? $this->Response : $this->Response = $this->assembleDependency('response_class');
    }

    protected function getRouter() {
        return null !== $this->Router ? $this->Router : $this->Router = $this->assembleDependency('router_class');
    }

}
