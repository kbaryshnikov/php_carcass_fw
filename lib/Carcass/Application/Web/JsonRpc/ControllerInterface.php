<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Http;

/**
 * Web_JsonRpc_ControllerInterface implementation is expected by JsonRpc router
 *
 * @package Carcass\Application
 */
interface Web_JsonRpc_ControllerInterface extends ControllerInterface {

    /**
     * @param Http\JsonRpc_Server $Server
     * @return void
     */
    public function dispatchRequestBody(Http\JsonRpc_Server $Server);

    /**
     * @param Http\JsonRpc_Server $Server
     * @return void
     */
    public function displayResponse(Http\JsonRpc_Server $Server);

}