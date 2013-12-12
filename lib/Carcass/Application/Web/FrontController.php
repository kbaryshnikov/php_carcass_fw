<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;
use Carcass\Config;

/**
 * Web front controller implementation
 *
 * @package Carcass\Application
 */
class Web_FrontController implements FrontControllerInterface {

    /** @var \Carcass\Corelib\Request */
    protected $Request;
    /** @var Web_Response */
    protected $Response;
    /** @var Web_Router_Interface */
    protected $Router;
    /** @var \Carcass\Config\ItemInterface */
    protected $Config;

    protected $is_dispatching_error = false;

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param Web_Response $Response
     * @param Web_Router_Interface $Router
     * @param \Carcass\Config\ItemInterface $WebConfig
     */
    public function __construct(Corelib\Request $Request, Web_Response $Response, Web_Router_Interface $Router, Config\ItemInterface $WebConfig = null) {
        $this->Request = $Request;
        $this->Response = $Response;
        $this->Router = $Router;
        $this->Config = $WebConfig;
    }

    public function run() {
        $debugger_is_enabled = DI::getDebugger()->isEnabled();
        register_shutdown_function(
            function () use ($debugger_is_enabled) {
                if ($e = error_get_last()) {
                    $this->showInternalError($debugger_is_enabled ? "$e[message] in $e[file] line $e[line]" : null);
                }
            }
        );
        try {
            $this->route();
        } catch (\Exception $e) {
            DI::getLogger()->logException($e);
            if ($debugger_is_enabled) {
                DI::getDebugger()->dumpException($e);
                $this->showInternalError(DI::getDebugger()->exceptionToString($e));
            } else {
                $this->showInternalError();
            }
        }
    }

    protected function route() {
        return $this->Router->route($this->Request, $this);
    }

    /**
     * @param $fq_action
     * @param \Carcass\Corelib\Hash $Args
     * @return mixed
     * @throws ImplementationNotFoundException
     */
    public function dispatch($fq_action, Corelib\Hash $Args) {
        list ($controller, $action) = Corelib\StringTools::split($fq_action, '.', [null, 'Default']);

        DI::getPathManager()->requirePage($controller);
        $page_fq_class = Instance::getFqClassName($controller);

        /** @var ControllerInterface $Page */
        $Page = new $page_fq_class($this->buildPageRequest($Args), $this->Response, $this->Router);
        return $this->dispatchPageAction($Page, $action, $Args);
    }

    protected function buildPageRequest(Corelib\Hash $Args) {
        $PageRequest = clone $this->Request;
        $PageRequest->Args->import($Args);
        return $PageRequest;
    }

    protected function dispatchPageAction(ControllerInterface $Page, $action, Corelib\Hash $Args) {
        $this->displayResult($Page->dispatch($action, $Args));
        return true;
    }

    /**
     * @param $result
     */
    protected function displayResult($result) {
        if ($result instanceof Web_Renderer_Interface) {
            $result->displayTo($this->Response);
        } elseif (is_string($result)) {
            $this->Response->write($result);
        } elseif (is_int($result)) {
            $this->dispatchHttpError($result);
        } else {
            $this->showInternalError("No result");
        }
    }

    /**
     * @param $error_message
     * @return void
     */
    public function dispatchNotFound($error_message) {
        $this->dispatchHttpError(404, $error_message);
    }

    /**
     * @param int $code
     * @param string|null $error_message
     */
    protected function dispatchHttpError($code, $error_message = null) {
        if (!$this->is_dispatching_error && null !== $error_route = $this->getErrorRoute($code)) {
            $this->is_dispatching_error = true;
            $this->dispatch($error_route, new Corelib\Hash(['code' => $code, 'error_message' => $error_message]));
        } else {
            $this->Response->writeHttpError($code, null, $error_message ? : $code);
        }
    }

    /**
     * @param $code
     * @return string|null
     */
    protected function getErrorRoute($code) {
        return strval($this->Config->getPath('error_handlers.error_' . $code)) ? : null;
    }

    /**
     * @param null $message
     */
    protected function showInternalError($message = null) {
        if (null === $message && $stub_file = $this->Config->get('unhandled_exception_stub_file')) {
            $this->Response->setStatus(500);
            try {
                $this->Response->write(
                    file_get_contents(DI::getPathManager()->getPathTo('var', $stub_file))
                );
                return;
            } catch (\Exception $e) {
                DI::getLogger()->logException($e);
                DI::getDebugger()->dumpException($e);
            }
        }
        $this->Response->writeHttpError(500, null, $message);
    }

}
