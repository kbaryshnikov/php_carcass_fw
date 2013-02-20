<?php

namespace Carcass\Log;

class SyslogWriter implements WriterInterface {

    const DEFAULT_IDENT = 'php';

    protected static $priority_map = [
        Level::ERROR         => LOG_ERR,
        Level::WARNING       => LOG_WARNING,
        Level::NOTICE        => LOG_NOTICE,
        Level::DEBUG         => LOG_DEBUG,
        Level::DEBUG_VERBOSE => LOG_DEBUG,
    ];

    public function __construct($ident = null, $facility = LOG_USER, $flags = null) {
        if (null === $ident) {
            $ident = static::DEFAULT_IDENT;
        }
        if (null === $flags) {
            $flags = LOG_PID | LOG_ODELAY | LOG_CONS;
        }
        openlog($ident, $flags, $facility);
    }

    public function __destruct() {
        closelog();
    }

    public function log(Message $Message) {
        syslog(static::$priority_map[$Message->getRawLevel()], $Message->getMessage());
        return $this;
    }

}
