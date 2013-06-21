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
 * Base controller implementation
 * @package Carcass\Application
 */
abstract class Controller implements ControllerInterface {

    /** @var \Carcass\Corelib\Request */
    protected $Request;
    /** @var \Carcass\Corelib\ResponseInterface */
    protected $Response;
    /** @var \Carcass\Application\RouterInterface */
    protected $Router;

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param \Carcass\Corelib\ResponseInterface $Response
     * @param RouterInterface $Router
     */
    public function __construct(Corelib\Request $Request, Corelib\ResponseInterface $Response, RouterInterface $Router) {
        $this->Request = $Request;
        $this->Response = $Response;
        $this->Router = $Router;
        $this->init();
    }

    /**
     * @param string $action Action name
     * @param \Carcass\Corelib\Hash $Args Action arguments
     * @throws ImplementationNotFoundException
     * @return mixed action results
     */
    public function dispatch($action, Corelib\Hash $Args) {
        $method = 'action' . $action;
        if (!method_exists($this, $method)) {
            throw new ImplementationNotFoundException("Action not implemented: '$action'");
        }
        return $this->$method($Args);
    }

    /**
     * @param string $error_message
     * @return void
     * @throws \RuntimeException
     */
    public function dispatchNotFound($error_message) {
        throw new \RuntimeException("Route not found: '$error_message'");
    }

    /**
     * @return Corelib\Request
     */
    protected function getRequest() {
        return $this->Request;
    }

    protected function init() {
        // pass
    }

}
