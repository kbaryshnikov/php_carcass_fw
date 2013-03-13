<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Http;

use Carcass\Corelib;
use Carcass\Application\DI;

/**
 * Class JsonRpc_Server
 * @package Carcass\Http
 */
class JsonRpc_Server {

    /**
     * @var callable (string $method, Corelib\Hash $Args, JsonRpc_Server $Server) -> array|bool
     */
    protected $DispatcherFn = null;

    protected $response_collector = [];
    protected $batch_mode = false;
    protected $catch_all = false;

    /**
     * @param callable $DispatcherFn
     */
    public function __construct(callable $DispatcherFn) {
        $this->DispatcherFn = $DispatcherFn;
    }

    /**
     * @param callable $RequestBodyProvider
     * @return $this
     */
    public function dispatchRequestBody(callable $RequestBodyProvider = null) {
        $this->dispatchJsonString($RequestBodyProvider ? $RequestBodyProvider() : file_get_contents('php://input'));
        return $this;
    }

    /**
     * @param $json_string
     * @return $this
     */
    public function dispatchJsonString($json_string) {
        $Request = null;
        try {
            $Request = JsonRpc_Request::factory($json_string, $this->batch_mode);
        } catch (JsonRpc_Exception $JsonRpcException) {
            $this->addErrorResponseFromException($JsonRpcException);
        }
        if ($Request) {
            $this->batch_mode ? $this->processBatch($Request) : $this->processRequest($Request);
        }
        return $this;
    }

    /**
     * @param bool $catch_all
     * @return $this
     */
    public function catchAllExceptions($catch_all = true) {
        $this->catch_all = (bool)$catch_all;
        return $this;
    }

    /**
     * @param JsonRpc_Request $Request
     * @throws \Exception
     * @return JsonRpc_Exception|null abort exception, only for batch mode internals
     */
    protected function processRequest(JsonRpc_Request $Request) {
        try {
            $result = $this->dispatchRequest($Request);
            if ($Request->getId()) {
                $this->addResultResponse($result, $Request->getId());
            }
        } catch (JsonRpc_Exception $JsonRpcException) {
            $this->addErrorResponseFromException($JsonRpcException->setId($Request->getId()));
            if ($this->batch_mode && $JsonRpcException->abortsBatchProcessing()) {
                return $JsonRpcException;
            }
        } catch (\Exception $e) {
            if (!$this->catch_all) {
                throw $e;
            }
            DI::getLogger()->logException($e);
            DI::getDebugger()->dumpException($e);
            $this->addErrorResponse(
                JsonRpc_Exception::ERR_SERVER_ERROR,
                DI::getDebugger()->isEnabled() ? $e->getMessage() . "\n" . $e->getTraceAsString() : "Internal Server Error"
            );
        }
        return null;
    }

    /**
     * @param JsonRpc_BatchRequest $Batch
     */
    protected function processBatch(JsonRpc_BatchRequest $Batch) {
        /** @var JsonRpc_Exception $AbortException */
        $AbortException = null;
        /** @var JsonRpc_Request $Request */
        foreach ($Batch as $Request) {
            if ($AbortException) {
                $this->addErrorResponseFromException($AbortException->setId($Request->getId()));
            } else {
                $AbortException = $this->processRequest($Request);
            }
        }
    }

    /**
     * @param Corelib\ResponseInterface $Response
     */
    public function displayTo(Corelib\ResponseInterface $Response) {
        $Response->write(Corelib\JsonTools::encode($this->getCollectedResponse()));
    }

    /**
     * @return array
     */
    public function getCollectedResponse() {
        if ($this->batch_mode) {
            $result = $this->response_collector;
        } else {
            $result = reset($this->response_collector);
        }
        return $result ?: [];
    }

    /**
     * @param JsonRpc_Request $Request
     * @return array
     * @throws JsonRpc_Exception
     */
    protected function dispatchRequest(JsonRpc_Request $Request) {
        $DispatcherFn = $this->DispatcherFn;
        $response = $DispatcherFn($Request->getMethod(), new Corelib\Hash($Request->getParams()), $this);
        if (is_bool($response)) {
            $response = ['success' => $response];
        } elseif (!is_array($response)) {
            DI::getLogger()->logWarning("Invalid response received from JSONRPC dispatcher function: " . var_export($response, true));
            throw JsonRpc_Exception::constructServerErrorException("Invalid method response received");
        }
        return $response;
    }

    /**
     * @param array $response
     */
    protected function addResponse(array $response) {
        $this->response_collector[] = ['jsonrpc' => '2.0'] + $response;
    }

    /**
     * @param $result
     * @param $id
     */
    protected function addResultResponse($result, $id) {
        $this->addResponse(compact('result', 'id'));
    }

    /**
     * @param $code
     * @param $message
     * @param null $id
     */
    protected function addErrorResponse($code, $message, $id = null) {
        $this->addResponse(
            [
                'error' => [
                    'code'    => $code,
                    'message' => $message,
                ],
                'id' => $id,
            ]
        );
    }

    /**
     * @param JsonRpc_Exception $e
     */
    protected function addErrorResponseFromException(JsonRpc_Exception $e) {
        $this->addErrorResponse($e->getCode(), $e->getMessage(), $e->getId());
    }

}