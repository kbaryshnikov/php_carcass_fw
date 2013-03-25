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

    /** @var Http\JsonRpc_Server */
    protected $JsonRpcServer = null;

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param Web_Response $Response
     * @param \Carcass\Application\Web_Router_JsonRpc $Router
     * @param \Carcass\Config\ItemInterface $WebConfig
     */
    public function __construct(Corelib\Request $Request, Web_Response $Response, Web_Router_JsonRpc $Router, Config\ItemInterface $WebConfig = null) {
        parent::__construct($Request, $Response, $Router, $WebConfig);
    }

    public function setJsonRpcServer(Http\JsonRpc_Server $Server) {
        $this->JsonRpcServer = $Server;
    }

    public function dispatchJsonRpc(Http\JsonRpc_Server $Server, $method, Corelib\Hash $Args) {
        $this->setJsonRpcServer($Server);
        $this->dispatch($method, $Args);
    }

    protected function route() {
        if (!parent::route()) {
            $this->displayResult('');
        }
    }

    protected function displayResult($result) {
        if (null !== $this->JsonRpcServer) {
            $this->JsonRpcServer->displayTo($this->Response);
        } else {
            parent::displayResult($result);
        }
    }

}