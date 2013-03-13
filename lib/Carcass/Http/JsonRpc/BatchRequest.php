<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Http;

use Carcass\Corelib;

/**
 * JsonRpc_BatchRequest
 * @package Carcass\Http
 */
class JsonRpc_BatchRequest implements \Countable, \ArrayAccess, \Iterator {
    use Corelib\ArrayObjectTrait;

    protected $requests = [];

    /**
     * @param array $json_requests
     */
    public function __construct(array $json_requests = null) {
        if ($json_requests) {
            foreach ($json_requests as $json_request) {
                $this->addFromJson((array)$json_request);
            }
        }
    }

    /**
     * @param $json
     * @return $this
     */
    public function addFromJson($json) {
        return $this->add(new JsonRpc_Request($json));
    }

    /**
     * @param JsonRpc_Request $Request
     * @return $this
     */
    public function add(JsonRpc_Request $Request) {
        $this->requests[] = $Request;
        return $this;
    }

    /** @noinspection PhpHierarchyChecksInspection */
    protected function &getDataArrayPtr() {
        return $this->requests;
    }

}