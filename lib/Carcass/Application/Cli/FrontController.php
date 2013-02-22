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
class Cli_FrontController implements ControllerInterface {

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
            Injector::getLogger()->logException($e);
            Injector::getDebugger()->dumpException($e);
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

        $script_class = "{$controller}Script";

        try {
            include_once Injector::getPathManager()->getPathToPhpFile('scripts', $script_class);
        } catch (WarningException $e) {
            $this->Response->setStatus(self::INPUT_ERROR)->writeErrorLn("Could not load '$script_class' implementation file: " . $e->getMessage());
            return;
        }

        $script_fq_class = Instance::getFqClassName($script_class);

        if (!class_exists($script_fq_class, false)) {
            $this->Response->setStatus(self::INPUT_ERROR)->writeErrorLn("No implementation exists for '$script_class'");
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
     */
    public function dispatchNotFound($error_message) {
        $this->Response->setStatus(self::INPUT_ERROR)->writeErrorLn($error_message);
    }

}
