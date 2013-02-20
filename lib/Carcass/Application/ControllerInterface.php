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
 * @package Carcass\Application
 */
interface ControllerInterface {

    /**
     * @param $action
     * @param \Carcass\Corelib\Hash $Args
     * @return mixed
     */
    public function dispatch($action, Corelib\Hash $Args);

    /**
     * @param $error_message
     * @return void
     */
    public function dispatchNotFound($error_message);

}
