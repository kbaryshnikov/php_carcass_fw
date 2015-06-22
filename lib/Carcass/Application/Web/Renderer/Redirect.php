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
 * Redirect renderer
 * @package Carcass\Application
 */
class Web_Renderer_Redirect extends Web_Renderer_Base {

    protected $url;

    /**
     * @param $url
     * @param int $status
     */
    public function __construct($url, $status = 302) {
        $this->url = $url;
        $this->setStatus($status);
    }

    protected function sendHeaders(Web_Response $Response) {
        parent::sendHeaders($Response);
        $Response->sendRedirect($this->url, $this->status);
    }

    protected function doRender(array $render_data) {
        return '';
    }

}
