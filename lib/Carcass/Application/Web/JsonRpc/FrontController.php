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

/**
 * JSON-RPC Front Controller
 *
 * @package Carcass\Application
 */
class Web_JsonRpc_FrontController extends Web_FrontController implements Web_JsonRpc_ControllerInterface {
    use Web_JsonRpc_ControllerTrait;

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param Web_Response $Response
     * @param \Carcass\Application\Web_Router_JsonRpc $Router
     * @param \Carcass\Config\ItemInterface $WebConfig
     */
    public function __construct(Corelib\Request $Request, Web_Response $Response, Web_Router_JsonRpc $Router, Config\ItemInterface $WebConfig = null) {
        parent::__construct($Request, $Response, $Router, $WebConfig);
    }
}