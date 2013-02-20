<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

/**
 * Implementations of RequestBuilderInterface must assemble
 * and return an instance of Corelib\Request.
 *
 * Coding style notice:
 * Only RequestBuilderInterface implementations are allowed to use PHP superglobals.
 *
 * @package Carcass\Application
 */
interface RequestBuilderInterface {

    /**
     * @param array $app_env
     * @return \Carcass\Corelib\Request
     */
    public static function assembleRequest(array $app_env = []);

}