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
 * Class Definitions
 * @package Carcass\Http
 */
class Definitions {

    const COOKIE_LIFETIME_FOREVER = 2147483647;

    /**
     * @param int $status
     * @param string|null $protocol_version HTTP protocol version (e.g. '1.0'), or null for cgi interface
     * @return string
     */
    public static function getStatusHeader($status, $protocol_version = null) {
        $status = (int)$status;
        static::validateStatusCode($status);
        if (null !== $protocol_version) {
            return "HTTP/{$protocol_version} $status " . static::getStatusText($status);
        } else {
            return "Status: $status";
        }
    }

    /**
     * @param $status
     * @return string
     */
    public static function getStatusText($status) {
        return isset(static::$rfc_statuses[$status]) ? static::$rfc_statuses[$status] : 'Unknown';
    }

    /**
     * @param $status
     */
    public static function validateStatusCode($status) {
        Corelib\Assert::that("'$status' is a valid HTTP status code'")->isUnsignedInt($status)->isInRange($status, 100, 599);
    }

    /**
     * @var array
     */
    protected static $rfc_statuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        451 => 'Unavailable for legal reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
    ];

}