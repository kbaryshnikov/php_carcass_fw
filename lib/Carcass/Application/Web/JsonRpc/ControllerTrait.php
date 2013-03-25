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
 * Web_JsonRpc_ControllerTrait, implementation of Web_JsonRpc_ControllerInterface
 *
 * @package Carcass\Application
 */
trait Web_JsonRpc_ControllerTrait {

    /**
     * @param Http\JsonRpc_Server $Server
     */
    public function dispatchRequestBody(Http\JsonRpc_Server $Server) {
        $Server->dispatchRequestBody($this->getRequestBodyProvider());
    }

    /**
     * @param Http\JsonRpc_Server $Server
     */
    public function displayResponse(Http\JsonRpc_Server $Server) {
        /** @noinspection PhpUndefinedFieldInspection */
        $Server->displayTo($this->Response);
    }

    /**
     * @return callable|null
     */
    protected function getRequestBodyProvider() {
        return null;
    }

}