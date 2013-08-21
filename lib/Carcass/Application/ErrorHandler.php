<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

/**
 * Application error handler. Convers PHP errors to exceptions according to error level.
 *
 * @package Carcass\Application
 */
class ErrorHandler {

    private static $class_by_errno = [
        E_USER_ERROR        => 'ErrorException',
        E_RECOVERABLE_ERROR => 'ErrorException',
        E_WARNING           => 'WarningException',
        E_USER_WARNING      => 'WarningException',
        E_CORE_WARNING      => 'WarningException',
        E_COMPILE_WARNING   => 'WarningException',
        E_NOTICE            => 'NoticeException',
        E_USER_NOTICE       => 'NoticeException',
        E_STRICT            => 'NoticeException',
        E_DEPRECATED        => 'NoticeException',
    ];

    /**
     * Error handler callback for set_error_handler()
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws
     */
    public static function __errorHandler($errno, $errstr, $errfile, $errline) {
        $error_reporting_level = error_reporting();
        if (
            ($error_reporting_level == 0) // @
            || ($error_reporting_level & $errno) != $errno // error reporting disabled for this level
        ) {
            return;
        }
        $exception = array_key_exists($errno, self::$class_by_errno) ? self::$class_by_errno[$errno] : 'ErrorException';
        $class = __NAMESPACE__ . '\\' . $exception;
        throw new $class($errstr, $errno, 0, $errfile, $errline);
    }

    /**
     * Registers the error handler
     *
     * @param int|null $level error level, null for current error_reporting value
     * @param callable|null $handler custom error handler function
     */
    public static function register($level = null, callable $handler = null) {
        set_error_handler($handler ? : [get_called_class(), '__errorHandler'], $level !== null ? $level : error_reporting());
    }

}

/**
 * Class ErrorException
 * @package Carcass\Application
 */
class ErrorException extends \ErrorException {

    /**
     * @return string
     */
    public function getLevel() {
        return 'Error';
    }

}

/**
 * Class WarningException
 * @package Carcass\Application
 */
class WarningException extends ErrorException {

    /**
     * @return string
     */
    public function getLevel() {
        return 'Warning';
    }

}

/**
 * Class NoticeException
 * @package Carcass\Application
 */
class NoticeException extends WarningException {

    /**
     * @return string
     */
    public function getLevel() {
        return 'Notice';
    }

}

