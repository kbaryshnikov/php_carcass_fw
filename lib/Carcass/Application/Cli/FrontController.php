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
 * Cli application front controller.
 * @package Carcass\Application
 */
class Cli_FrontController implements FrontControllerInterface {

    const INPUT_ERROR = 1;
    const INTERNAL_ERROR = 255;

    /**
     * @var \Carcass\Corelib\Request
     */
    protected $Request;
    /**
     * @var \Carcass\Corelib\Response
     */
    protected $Response;
    /**
     * @var RouterInterface
     */
    protected $Router;

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param \Carcass\Corelib\Response $Response
     * @param RouterInterface $Router
     */
    public function __construct(Corelib\Request $Request, Corelib\Response $Response, RouterInterface $Router) {
        $this->Request  = $Request;
        $this->Response = $Response;
        $this->Router   = $Router;
    }

    /**
     * Executes the controller.
     */
    public function run() {
        try {
            $this->Router->route($this->Request, $this);
        } catch (\Exception $e) {
            DI::getLogger()->logException($e);
            DI::getDebugger()->dumpException($e);
            $this->Response->setStatus(self::INTERNAL_ERROR);
        }
        exit($this->Response->getStatus());
    }

    /**
     * Dispatches the action.
     * @param string $fq_action fully-qualified action name
     * @param \Carcass\Corelib\Hash $Args
     * @return void
     */
    public function dispatch($fq_action, Corelib\Hash $Args) {
        list ($controller, $action) = Corelib\StringTools::split($fq_action, '.', [null, 'Default']);

        try {
            DI::getPathManager()->requireScript($controller);
        } catch (ImplementationNotFoundException $e) {
            $this->Response->setStatus(self::INPUT_ERROR)->writeErrorLn("Controller for $fq_action not exists");
            return;
        }

        $script_fq_class = Instance::getFqClassName($controller);

        if (!class_exists($script_fq_class, false)) {
            $this->Response->setStatus(self::INPUT_ERROR)->writeErrorLn("No implementation exists for '$fq_action'");
            return;
        }

        /** @var ControllerInterface $Script */
        $Script = new $script_fq_class($this->Request, $this->Response, $this->Router);
        $status = $Script->dispatch($action, $Args);

        if ($status) {
            $this->Response->setStatus($status);
        }
    }

    /**
     * @param $error_message
     * @return void
     */
    public function dispatchNotFound($error_message) {
        $this->Response->setStatus(self::INPUT_ERROR)->writeErrorLn($error_message);
    }

}
