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
 * JsonRpc exception
 * @package Carcass\Http
 */
class JsonRpc_Exception extends \RuntimeException {

    const
        ERR_PARSE_ERROR      = -32700,
        ERR_INVALID_REQUEST  = -32600,
        ERR_METHOD_NOT_FOUND = -32601,
        ERR_INVALID_PARAMS   = -32602,
        ERR_INTERNAL_ERROR   = -32603,
        ERR_SERVER_ERROR     = -32000,
        ERR_BATCH_ABORTED    = -32099;

    protected static $server_error_range = [-32099, -32000];

    protected static $error_messages_by_code = [
        self::ERR_PARSE_ERROR      => 'Parse error',
        self::ERR_INVALID_REQUEST  => 'Invalid Request',
        self::ERR_METHOD_NOT_FOUND => 'Method not found',
        self::ERR_INVALID_PARAMS   => 'Invalid method parameters',
        self::ERR_INTERNAL_ERROR   => 'Internal error',
        self::ERR_SERVER_ERROR     => 'Server error',
    ];

    protected $abort_batch = false;

    /**
     * @param string|string|null $message
     * @return JsonRpc_Exception
     */
    public static function constructParseErrorException($message = null) {
        return new self(self::ERR_PARSE_ERROR, $message);
    }

    /**
     * @param string|null $message
     * @return JsonRpc_Exception
     */
    public static function constructInvalidRequestException($message = null) {
        return new self(self::ERR_INVALID_REQUEST, $message);
    }

    /**
     * @param string|null $message
     * @return JsonRpc_Exception
     */
    public static function constructMethodNotFoundException($message = null) {
        return new self(self::ERR_METHOD_NOT_FOUND, $message);
    }

    /**
     * @param string|null $message
     * @return JsonRpc_Exception
     */
    public static function constructInvalidParamsException($message = null) {
        return new self(self::ERR_INVALID_PARAMS, $message);
    }

    /**
     * @param string|null $message
     * @return JsonRpc_Exception
     */
    public static function constructInternalErrorException($message = null) {
        return new self(self::ERR_INTERNAL_ERROR, $message);
    }

    /**
     * @param string|null $message
     * @param int|null $code -32000 to -32099
     * @throws \InvalidArgumentException
     * @return JsonRpc_Exception
     */
    public static function constructServerErrorException($message = null, $code = null) {
        if (null !== $code && !self::isServerErrorCode($code)) {
            throw new \InvalidArgumentException("Invalid server error code '$code': not in range");
        }
        return new self($code ? : self::ERR_SERVER_ERROR, $message);
    }

    /**
     * @param string|null $message
     * @return JsonRpc_Exception
     */
    public static function constructAbortBatch($message = null) {
        return self::constructServerErrorException($message ? : 'Batch aborted', self::ERR_BATCH_ABORTED)->setAbortBatchProcessing();
    }

    /**
     * @param int $code one of ERR_ constants
     * @param string|null $message
     */
    public function __construct($code, $message = null) {
        if (self::isServerErrorCode($code)) {
            $error_string = self::$error_messages_by_code[self::ERR_SERVER_ERROR];
        } elseif (!isset(self::$error_messages_by_code[$code])) {
            $code         = self::ERR_INTERNAL_ERROR;
            $error_string = "Invalid error code: $code." . ($message === null ? '' : " Additionally: $message");
        } else {
            $error_string = self::$error_messages_by_code[$code];
        }
        parent::__construct($error_string . ($message === null ? '' : ": $message"), $code);
    }

    /**
     * @return bool
     */
    public function abortsBatchProcessing() {
        return $this->abort_batch;
    }

    /**
     * @param bool $abort_batch_processing
     * @return self
     */
    public function setAbortBatchProcessing($abort_batch_processing = true) {
        $this->abort_batch = (bool)$abort_batch_processing;
        return $this;
    }

    /**
     * @param int $code
     * @return bool
     */
    protected static function isServerErrorCode($code) {
        return ($code >= self::$server_error_range[0] && $code <= self::$server_error_range[1]);
    }
}