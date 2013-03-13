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
 * Web router interface.
 * A web router must additionally be able to build urls.
 *
 * @package Carcass\Application
 */
interface Web_Router_Interface extends RouterInterface {

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param $route
     * @param array $args
     * @return string
     */
    public function getUrl(Corelib\Request $Request, $route, array $args = []);

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param $route
     * @param array $args
     * @return string
     */
    public function getAbsoluteUrl(Corelib\Request $Request, $route, array $args = []);

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param $url
     * @param string|bool $host string for exact hostname, true to use the default static host, false for no hostname
     * @param string|null $scheme
     * @return string
     */
    public function getStaticUrl(Corelib\Request $Request, $url, $host = false, $scheme = null);

}
