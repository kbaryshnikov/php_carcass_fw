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
 * Cli application router.
 * @package Carcass\Application
 */
class Cli_Router implements RouterInterface {

    const DEFAULT_CONTROLLER_NAME = 'Default';
    const DEFAULT_CONTROLLER_SUFFIX = 'Script';
    const DEFAULT_ACTION_NAME = 'Default';

    protected $default_controller_name = self::DEFAULT_CONTROLLER_NAME;
    protected $controller_suffix = self::DEFAULT_CONTROLLER_SUFFIX;
    protected $default_action_name = self::DEFAULT_ACTION_NAME;

    /**
     * Takes the first unnamed argument as script name (or scriptName.action) for dispatching.
     * If the argument is empty, dispatches the default.
     * @param \Carcass\Corelib\Request $Request
     * @param ControllerInterface $Controller
     * @return mixed
     */
    public function route(Corelib\Request $Request, ControllerInterface $Controller) {
        $dispatcher_name = $this->getDispatcherName($Request->Args->get(0));
        try {
            return $Controller->dispatch($dispatcher_name, $Request->Args);
        } catch (ImplementationNotFoundException $e) {
            return $Controller->dispatchNotFound("{$dispatcher_name}: {$e->getMessage()}");
        }
    }

    /**
     * Changes the default script and action name. null values restore defaults
     * @param string|null $controller_name
     * @param string|null $action_name
     * @return $this
     */
    public function setDefaultRoute($controller_name = null, $action_name = null) {
        $this->default_controller_name = $controller_name ? : self::DEFAULT_CONTROLLER_NAME;
        $this->default_action_name = $action_name ? : self::DEFAULT_ACTION_NAME;
        return $this;
    }

    /**
     * Changes the default controller name suffix
     * @param string|null $suffix, null restores the default
     * @return $this
     */
    public function setControllerSuffix($suffix) {
        $this->controller_suffix = $suffix ? : self::DEFAULT_CONTROLLER_SUFFIX;
        return $this;
    }

    protected function getDispatcherName($argument) {
        list ($controller, $action) = Corelib\StringTools::split($argument, '.', 2, [$this->default_controller_name, $this->default_action_name]);
        $controller = (join('', array_map('ucfirst', explode('-', $controller))) ? : $this->default_controller_name) . $this->controller_suffix;
        return "{$controller}.${action}";
    }

}
