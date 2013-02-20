<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

/**
 * Front controller interface. Just adds the run method.
 * @package Carcass\Application
 */
interface FrontControllerInterface extends ControllerInterface {

    /**
     * Executes the front controller
     */
    public function run();

}
