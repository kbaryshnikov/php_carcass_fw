<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Http;

use Carcass\Application\Web_Response;
use Carcass\Corelib;
use Carcass\Application\DI;
use Carcass\Corelib\ResultInterface;

/**
 * Class JsonRpc_Server
 * @package Carcass\Http
 */
class JsonRpc_Server implements Corelib\RenderableInterface {

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
     * @return bool
     */
    public function dispatchRequestBody(callable $RequestBodyProvider = null) {
        return $this->dispatchJsonString($RequestBodyProvider ? $RequestBodyProvider() : file_get_contents('php://input'));
    }

    /**
     * @param $json_string
     * @return bool
     */
    public function dispatchJsonString($json_string) {
        /** @var $Request JsonRpc_Request|JsonRpc_BatchRequest */
        $Request = null;
        try {
            $Request = JsonRpc_Request::factory($json_string, $this->batch_mode);
        } catch (JsonRpc_Exception $JsonRpcException) {
            $this->addErrorResponseFromException($JsonRpcException, $Request && $Request->getId() ? $Request->getId() : null);
            return false;
        }
        return true === ($this->batch_mode ? $this->processBatch($Request) : $this->processRequest($Request));
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
     * @return JsonRpc_Exception|bool|null  result, or abort exception (only for batch mode internals)
     */
    protected function processRequest(JsonRpc_Request $Request) {
        $result = false;
        try {
            $response = $this->dispatchRequest($Request);
            if ($Request->getId()) {
                $this->addResultResponse($response, $Request->getId());
            }
            $result = true;
        } catch (JsonRpc_Exception $JsonRpcException) {
            $Request->getId() and $this->addErrorResponseFromException($JsonRpcException, $Request->getId());
            if ($this->batch_mode && $JsonRpcException->abortsBatchProcessing()) {
                return $JsonRpcException;
            }
            $result = false;
        } catch (\Exception $e) {
            if (!$this->catch_all) {
                throw $e;
            }
            DI::getLogger()->logException($e);
            DI::getDebugger()->dumpException($e);
            $Request->getId() and $this->addErrorResponse(
                JsonRpc_Exception::ERR_SERVER_ERROR,
                DI::getDebugger()->isEnabled()
                    ?
                    [
                        'exception' => get_class($e),
                        'message'   => $e->getMessage(),
                        'file'      => $e->getFile(),
                        'line'      => $e->getLine(),
                        'backtrace' => explode("\n", $e->getTraceAsString()),
                    ]
                    : "Internal Server Error",
                $Request->getId()
            );
            if ($this->batch_mode) {
                return $e;
            }
        }
        return $result;
    }

    /**
     * @param JsonRpc_BatchRequest $Batch
     * @return bool
     */
    protected function processBatch(JsonRpc_BatchRequest $Batch) {
        /** @var JsonRpc_Exception $AbortException */
        $AbortException = null;
        /** @var JsonRpc_Request $Request */
        foreach ($Batch as $Request) {
            if ($AbortException) {
                if ($Request->getId()) {
                    $this->addErrorResponseFromException($AbortException, $Request->getId());
                }
            } else {
                $result = $this->processRequest($Request);
                $AbortException = $result instanceof \Exception ? $result : null;
                if ($AbortException && !$AbortException instanceof JsonRpc_Exception) {
                    break;
                }
            }
        }
        return false;
    }

    /**
     * @param Corelib\ResponseInterface $Response
     */
    public function displayTo(Corelib\ResponseInterface $Response) {
        if ($Response instanceof Web_Response) {
            $Response->sendHeader('Content-Type', 'application/json; charset=utf8');
        }
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
        return $result ? : [];
    }

    /**
     * @return $this
     */
    public function cleanCollectedResponse() {
        $this->response_collector = [];
        return $this;
    }

    /**
     * @return bool
     */
    public function isBatchMode() {
        return $this->batch_mode;
    }

    /**
     * @param JsonRpc_Request $Request
     * @return array
     * @throws JsonRpc_Exception
     */
    protected function dispatchRequest(JsonRpc_Request $Request) {
        $DispatcherFn = $this->DispatcherFn;
        $response = $DispatcherFn(
            $Request->getMethod(),
            (new Corelib\Hash([
                'JsonRpc' => [
                    'Server'  => $this,
                    'Request' => $Request,
                ]
            ]))->merge($Request->getParams()),
            $this
        );
        if (is_bool($response)) {
            $response = ['success' => $response];
        } elseif ($response instanceof Corelib\ExportableInterface) {
            $response = $response->exportArray();
        }
        if (!is_array($response) && !is_scalar($response)) {
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
     * @param $id
     */
    protected function addErrorResponse($code, $message, $id = null) {
        $this->addResponse(
            [
                'error' => [
                    'code'    => $code,
                    'message' => $message,
                ],
                'id'    => $id,
            ]
        );
    }

    /**
     * @param JsonRpc_Exception $e
     * @param $id
     */
    protected function addErrorResponseFromException(JsonRpc_Exception $e, $id) {
        $this->addErrorResponse($e->getCode(), $e->getMessage(), $id);
    }

    /**
     * @param ResultInterface $View
     * @return $this
     */
    public function renderTo(ResultInterface $View) {
        $View->assign($this->getCollectedResponse());
        return $this;
    }
}