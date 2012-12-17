<?php

namespace Carcass\DevTools;

class FirebugReporter extends BaseReporter {

    protected $FireLogger;

    protected static $markers = [
        'exception'     => 'critical',
        'critical'      => 'critical',
        'error'         => 'error',
        'failed'        => 'error',
        'warning'       => 'warning',
        'timers'        => 'info',
    ];

    protected static $marker_letters = [
        '!' => 'critical',
        'e' => 'error',
        'w' => 'warning',
        'i' => 'info',
    ];

    public function __construct($FireLogger = null) {
        if (null !== $FireLogger) {
            $this->setFireLogger($FireLogger);
        } else {
            $this->ensureStandardFireLoggerIsLoaded();
            $this->setFireLogger(new FireLogger);
            FireLogger::$enabled = true;
        }
    }

    public function setFireLogger($FireLogger) {
        $this->FireLogger = $FireLogger;
        return $this;
    }

    public function dump($value, $severity = null) {
        if (isset($severity) || $severity = $this->detectSeverity($value)) {
            $this->FireLogger->log($severity, $value);
        } else {
            $this->FireLogger->log($value);
        }
        return $this;
    }

    protected function detectSeverity($value) {
        $txt = strtolower(serialize($value));
        foreach (self::$markers as $substring => $severity) {
            if (false !== strpos($txt, $substring)) {
                return $severity;
            }
        }
        return false;
    }

    public function dumpException(Exception $Exception) {
        $this->FireLogger->log('critical', $Exception);
        return $this;
    }

    public function ensureStandardFireLoggerIsLoaded() {
        if (class_exists('\FireLogger', false)) {
            return;
        }

        require_once __DIR__ . '/firelogger.php';

        \FireLogger::$NO_EXCEPTION_HANDLER = true;
        \FireLogger::$NO_ERROR_HANDLER = true;
        \FireLogger::$NO_DEFAULT_LOGGER = true;

        \FireLoggerInitializer::init();
    }

}
