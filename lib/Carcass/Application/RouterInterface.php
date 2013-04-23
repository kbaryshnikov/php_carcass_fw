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
 * Router Interface
 * @package Carcass\Application
 */
interface RouterInterface {

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param ControllerInterface $Controller
     * @return mixed
     */
    public function route(Corelib\Request $Request, ControllerInterface $Controller);

}
