<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\DevTools;

use \FireLogger;

/**
 * FirebugReporter
 * @package Carcass\DevTools
 */
class FirebugReporter extends BaseReporter {

    /**
     * @var FireLogger
     */
    protected $FireLogger;

    protected static $markers = [
        'exception' => 'critical',
        'critical'  => 'critical',
        'error'     => 'error',
        'failed'    => 'error',
        'warning'   => 'warning',
        'timers'    => 'info',
    ];

    protected static $marker_letters = [
        '!' => 'critical',
        'e' => 'error',
        'w' => 'warning',
        'i' => 'info',
    ];

    /**
     * @param FireLogger|null $FireLogger
     */
    public function __construct($FireLogger = null) {
        if (null !== $FireLogger) {
            $this->setFireLogger($FireLogger);
        } else {
            self::ensureFireLoggerLibraryIsLoaded();
            $this->setFireLogger(new FireLogger);
            FireLogger::$enabled = true;
        }
    }

    /**
     * @param FireLogger $FireLogger
     * @return $this
     */
    public function setFireLogger(FireLogger $FireLogger) {
        $this->FireLogger = $FireLogger;
        return $this;
    }

    /**
     * @param mixed $value
     * @param $severity
     * @return $this
     */
    public function dump($value, $severity = null) {
        if (isset($severity) || $severity = $this->detectSeverity($value)) {
            $this->FireLogger->log($severity, $value);
        } else {
            $this->FireLogger->log($value);
        }
        return $this;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function detectSeverity($value) {
        $txt = strtolower(serialize($value));
        foreach (self::$markers as $substring => $severity) {
            if (false !== strpos($txt, $substring)) {
                return $severity;
            }
        }
        return false;
    }

    /**
     * @param \Exception $Exception
     * @return $this
     */
    public function dumpException(\Exception $Exception) {
        $this->FireLogger->log('critical', $Exception);
        return $this;
    }

    public static function ensureFireLoggerLibraryIsLoaded() {
        if (!class_exists('\FireLogger')) {
            throw new \RuntimeException("Could not load the FireLogger class");
        }

        foreach (self::$firelogger_definitions as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    private static $firelogger_definitions = [
        'FIRELOGGER_NO_EXCEPTION_HANDLER' => true,
        'FIRELOGGER_NO_ERROR_HANDLER'     => true,
        'FIRELOGGER_NO_DEFAULT_LOGGER'    => true,
        'FIRELOGGER_NO_CONFLICT'          => true,
    ];

}
