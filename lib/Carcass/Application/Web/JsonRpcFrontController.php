<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;
use Carcass\Config;
use Carcass\Http;

class Web_JsonRpcFrontController extends Web_FrontController {

    protected $displayed = false;

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param Web_Response $Response
     * @param \Carcass\Application\Web_Router_JsonRpc $Router
     * @param \Carcass\Config\ItemInterface $WebConfig
     */
    public function __construct(Corelib\Request $Request, Web_Response $Response, Web_Router_JsonRpc $Router, Config\ItemInterface $WebConfig = null) {
        parent::__construct($Request, $Response, $Router, $WebConfig);
    }

    public function displayJsonRpcResults(Http\JsonRpc_Server $Server) {
        $this->Response->sendHeader('Content-Type', 'application/json; charset=utf8');
        $Server->displayTo($this->Response);
    }

    protected function dispatchPageAction(ControllerInterface $Page, $action, Corelib\Hash $Args) {
        return $Page->dispatch($action, $Args);
    }

    protected function displayResult($result) {
        if (!$this->displayed) {
            parent::displayResult($result ?: 500);
        }
    }

}