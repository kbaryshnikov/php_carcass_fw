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
 * Sendfile redirect renderer: sends the internal redirect (web server-specific).
 * @package Carcass\Application
 */
class Web_Renderer_Sendfile extends Web_Renderer_Base {

    protected $location;

    /**
     * @param string $location
     */
    public function __construct($location) {
        $this->location = $location;
    }

    /**
     * @param Web_Response $Response
     * The responsibility of web server specific internal redirection is on Response::sendInternalRedirect method.
     */
    protected function sendHeaders(Web_Response $Response) {
        parent::sendHeaders($Response);
        $Response->sendInternalRedirect($this->location);
    }

    protected function doRender(array $render_data) {
        return '';
    }

}

