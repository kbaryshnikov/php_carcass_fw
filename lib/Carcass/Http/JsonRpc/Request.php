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
 * JSON-RPC 2.0 request
 *
 * @package Carcass\Http
 */
class JsonRpc_Request {

    /**
     * @var string|int|null
     */
    protected $id = null;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param array $json
     */
    public function __construct(array $json) {
        $this->loadFromJson($json);
    }

    /**
     * @return int|null|string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Request factory method
     *
     * @param $json_string
     * @param bool $is_batch by reference
     * @throws JsonRpc_Exception
     * @return JsonRpc_Request|JsonRpc_BatchRequest
     */
    public static function factory($json_string, &$is_batch = false) {
        try {
            $json = Corelib\JsonTools::decodeAsObject($json_string, null);
        } catch (\Exception $e) {
            throw JsonRpc_Exception::constructParseErrorException('Malformed JSON received: ' . $e->getMessage());
        }
        if (is_array($json)) {
            $is_batch = true;
            $Batch = new JsonRpc_BatchRequest($json);
            if (!count($Batch)) {
                throw JsonRpc_Exception::constructInvalidRequestException("Got an empty batch");
            }
            return $Batch;
        } else {
            if (!is_object($json)) {
                throw JsonRpc_Exception::constructParseErrorException();
            }
            return new static((array)$json);
        }
    }

    /**
     * @param array $json
     * @throws JsonRpc_Exception
     */
    protected function loadFromJson(array $json) {
        if (!isset($json['jsonrpc']) || '2.0' != $json['jsonrpc']) {
            throw JsonRpc_Exception::constructInvalidRequestException('jsonrpc version 2.0 is required');
        }

        if (!isset($json['method'])) {
            throw JsonRpc_Exception::constructInvalidRequestException("Missing method");
        }

        if (!is_string($json['method']) || !strlen($json['method'])) {
            throw JsonRpc_Exception::constructInvalidRequestException("Bad method: " . json_encode($json['method']));
        }
        $this->method = $json['method'];

        if (isset($json['params'])) {
            if (is_object($json['params'])) {
                $json['params'] = static::objectToArray($json['params']);
            } elseif (!is_array($json['params'])) {
                throw JsonRpc_Exception::constructInvalidRequestException('params must be an array or a key-value object');
            }
            $this->params = $json['params'];
        }

        if (array_key_exists('id', $json)) {
            if (null !== $json['id'] && !is_int($json['id']) && !is_string($json['id'])) {
                throw JsonRpc_Exception::constructInvalidRequestException('id must be null, int, or string');
            }
            $this->id = $json['id'];
        }
    }

    protected static function objectToArray($params) {
        $result = (array)$params;
        foreach ($result as $key => $value) {
            if (is_object($value)) {
                $result[$key] = static::objectToArray($value);
            }
        }
        return $result;
    }

}