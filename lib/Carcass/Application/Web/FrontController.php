<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_FrontController implements ControllerInterface {
    use Corelib\DependencyFactoryTrait;

    protected
        $Request,
        $Response = null,
        $Router = null,
        $Config = null;

    public function __construct(Request $Request, array $dependency_classes) {
        $this->Request = $Request;
        $this->dependency_classes = $dependency_classes;

        $ConfigReader = Instance::getConfigReader();
        if (!$ConfigReader->has('web')) {
            throw new \RuntimeException('No web configuration found');
        }
        $this->Config = $ConfigReader->web;
    }

    public function run() {
        try {
            $this->getRouter()->route($this->Request, $this);
        } catch (\Exception $e) {
            Instance::getLogger()->logException($e);
            if (Instance::getDebugger()->isEnabled()) {
                Instance::getDebugger()->dumpException($e);
                $this->showInternalError(Instance::getDebugger()->exceptionToString($e));
            } else {
                $this->showInternalError();
            }
        }
        $this->getResponse()->commit();
    }

    public function dispatch($fq_action, Corelib\Hash $Args) {
        list ($controller, $action) = Corelib\StringTools::split($fq_action, '.', [null, 'Default']);

        $page_class = "{$controller}Page";

        include_once Instance::getPathManager()->getPathToPhpFile('page', $page_class);

        $page_fq_class = Instance::getFqClassName($page_class);

        $Page = new $page_fq_class($this->Request, $this->getResponse(), $this->getRouter());
        $Page->dispatch($action, $Args);
    }

    public function dispatchNotFound($error_message) {
        $this->getResponse()->writeHttpError(404, null, $error_message);
    }

    protected function showInternalError($message = null) {
        if (null === $message && $this->Config->has('unhandled_exception_stub_file')) {
            $this->getResponse()->setStatus(500);
            try {
                $this->getResponse()->write(
                    file_get_contents(Instance::getPathManager()->getPath('var', $this->Config->unhandled_exception_stub_file))
                );
                return;
            } catch (\Exception $e) {
                Instance::getLogger()->logException($e);
                Instance::getDebugger()->dumpException($e);
            }
        }
        $this->getResponse()->writeHttpError(500, null, $message);
    }

    protected function getResponse() {
        return null !== $this->Response
            ? $this->Response
            : $this->Response = $this->assembleDependency('response_class', [$this->Request]);
    }

    protected function getRouter() {
        return null !== $this->Router
            ? $this->Router
            : $this->Router = $this->assembleDependency('router_class', [$this->Config]);
    }

}
