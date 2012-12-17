<?php

namespace Carcass\Application;

class ErrorException extends \ErrorException  {

    public function getLevel() {
        return 'Error';
    }

}

class WarningException extends ErrorException {

    public function getLevel() {
        return 'Warning';
    }

}

class NoticeException extends WarningException {

    public function getLevel() {
        return 'Notice';
    }

}

class ErrorHandler {

    static private $class_by_errno = [
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

    static public function __errorHandler($errno, $errstr, $errfile, $errline) {
        $error_reporting_level = error_reporting();
        if (
            ($error_reporting_level == 0) // @
         || ($error_reporting_level & $errno) != $errno // error reporting disabled for this level
        ) {
            return;
        }
        $class = __NAMESPACE__ . '\\' . (array_key_exists($errno, self::$class_by_errno) ? self::$class_by_errno[$errno] : 'ErrorException');
        throw new $class($errstr, $errno, 0, $errfile, $errline);
    }

    static public function register($level = null) {
        set_error_handler( [get_called_class(), '__errorHandler'], $level !== null ? $level : error_reporting() ); 
    }

}
