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

    const DEFAULT_DISPATCH_NAME = 'Default.Default';

    protected $default_dispatch_name = self::DEFAULT_DISPATCH_NAME;

    /**
     * Takes the first unnamed argument as script name (or scriptName.action) for dispatching.
     * If the argument is empty, dispatches the default.
     * @param \Carcass\Corelib\Request $Request
     * @param ControllerInterface $Controller
     */
    public function route(Corelib\Request $Request, ControllerInterface $Controller) {
        $Controller->dispatch(
            $Request->Args->get(0) ?: $this->default_dispatch_name,
            $Request->Args
        );
    }

    /**
     * Changes the default script and action name
     * @param string $name, e.g. 'script', or 'script.action'
     * @return $this
     */
    public function setDefaultDispatchName($name) {
        $this->default_dispatch_name = $name;
        return $this;
    }

}
