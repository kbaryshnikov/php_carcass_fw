<?php

namespace Carcass\Application;

use Carcass\Corelib;
use Carcass\Config;

class Web_FrontController implements ControllerInterface {

    protected
        $Request,
        $Response,
        $Router,
        $Config;

    public function __construct(Corelib\Request $Request, Corelib\Response $Response, RouterInterface $Router, Config\Item $WebConfig = null) {
        $this->Request = $Request;
        $this->Response = $Response;
        $this->Router = $Router;
        $this->Config = $WebConfig;
    }

    public function run() {
        $debugger_is_enabled = Injector::getDebugger()->isEnabled();
        register_shutdown_function(function() use ($debugger_is_enabled) {
            if ($e = error_get_last()) {
                $this->showInternalError( $debugger_is_enabled ? "$e[message] in $e[file] line $e[line]" : null );
            }
        });
        try {
            $this->getRouter()->route($this->Request, $this);
        } catch (\Exception $e) {
            Injector::getLogger()->logException($e);
            if ($debugger_is_enabled) {
                Injector::getDebugger()->dumpException($e);
                $this->showInternalError(Injector::getDebugger()->exceptionToString($e));
            } else {
                $this->showInternalError();
            }
        }
        $this->getResponse()->isBuffering() and $this->getResponse()->commit();
    }

    public function dispatch($fq_action, Corelib\Hash $Args) {
        list ($controller, $action) = Corelib\StringTools::split($fq_action, '.', [null, 'Default']);

        $page_class = "{$controller}Page";

        include_once Injector::getPathManager()->getPathToPhpFile('pages', $page_class);

        $page_fq_class = Instance::getFqClassName($page_class);

        $Page = new $page_fq_class($this->Request, $this->getResponse(), $this->getRouter());
        $this->displayResult($Page->dispatch($action, $Args));
    }

    protected function displayResult($result) {
        if ($result instanceof Web_Renderer_Interface) {
            $result->displayTo($this->getResponse());
        } elseif (is_string($result)) {
            $this->getResponse()->write($result);
        } elseif (is_int($result)) {
            $this->getResponse()->writeHttpError($result);
        } else {
            $this->showInternalError("No result");
        }
    }

    public function dispatchNotFound($error_message) {
        $this->getResponse()->writeHttpError(404, null, $error_message);
    }

    protected function showInternalError($message = null) {
        if (null === $message && $this->Config->has('unhandled_exception_stub_file')) {
            $this->getResponse()->setStatus(500);
            try {
                $this->getResponse()->write(
                    file_get_contents(Injector::getPathManager()->getPath('var', $this->Config->unhandled_exception_stub_file))
                );
                return;
            } catch (\Exception $e) {
                Injector::getLogger()->logException($e);
                Injector::getDebugger()->dumpException($e);
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
