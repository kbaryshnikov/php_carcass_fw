<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Log;

use Carcass\Corelib;
use Carcass\Application;

/**
 * Log Dispatcher
 * @package Carcass\Log
 */
class Dispatcher {

    /**
     * @var Corelib\Hash[]
     */
    protected $destinations = [];

    /**
     * @param array $destinations_list
     */
    public function __construct(array $destinations_list = []) {
        $destinations_list and $this->setDestinations($destinations_list);
    }

    /**
     * @param array $list
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setDestinations(array $list) {
        foreach ($list as $writer_name => $config) {
            if (!is_array($config)) {
                throw new \InvalidArgumentException('Invalid list format: list items must be arrays');
            }
            $level = array_shift($config);
            $args = array_shift($config);
            $Writer = WriterFactory::assemble($writer_name, is_array($args) ? $args : []);
            $this->addDestination($Writer, $level);
        }
        return $this;
    }

    /**
     * @param WriterInterface $Writer
     * @param $level
     * @return $this
     */
    public function addDestination(WriterInterface $Writer, $level) {
        $this->destinations[] = new Corelib\Hash(['Writer' => $Writer, 'level' => static::resolveLevel($level)]);
        return $this;
    }

    /**
     * @param \Exception $e
     * @param null $level
     * @return $this
     */
    public function logException(\Exception $e, $level = null) {
        $this->logEvent($level ? : static::getExceptionLevel($e), static::formatExceptionMessage($e));
        return $this;
    }

    /**
     * @param $level
     * @param $message
     * @param bool $add_caller_info
     * @return $this
     */
    public function logEvent($level, $message, $add_caller_info = true) {
        $level = static::resolveLevel($level);
        foreach ($this->destinations as $Destination) {
            if ($level >= $Destination->level) {
                if (!isset($Message)) {
                    if (!is_scalar($message)) {
                        $message = null === $message ? 'null' : print_r($message, true);
                    } elseif (is_bool($message)) {
                        $message = $message ? 'true' : 'false';
                    }
                    if ($add_caller_info) {
                        $message = sprintf("<%s> %s", $this->getCaller(), $message);
                    }
                    $Message = new Message($message, $level);
                }
                $Destination->Writer->log($Message);
            }
        }
        return $this;
    }

    /**
     * @param $message
     * @param bool $add_caller_info
     * @return $this
     */
    public function logError($message, $add_caller_info = true) {
        return $this->logEvent(Level::ERROR, $message, $add_caller_info);
    }

    /**
     * @param $message
     * @param bool $add_caller_info
     * @return $this
     */
    public function logWarning($message, $add_caller_info = true) {
        return $this->logEvent(Level::WARNING, $message, $add_caller_info);
    }

    /**
     * @param $message
     * @param bool $add_caller_info
     * @return $this
     */
    public function logNotice($message, $add_caller_info = true) {
        return $this->logEvent(Level::NOTICE, $message, $add_caller_info);
    }

    /**
     * @param $message
     * @param bool $add_caller_info
     * @return $this
     */
    public function logDebug($message, $add_caller_info = true) {
        return $this->logEvent(Level::DEBUG, $message, $add_caller_info);
    }

    /**
     * @param $message
     * @param bool $add_caller_info
     * @return $this
     */
    public function logDebugVerbose($message, $add_caller_info = true) {
        return $this->logEvent(Level::DEBUG_VERBOSE, $message, $add_caller_info);
    }

    /**
     * @return string
     */
    protected function getCaller() {
        foreach (debug_backtrace() as $trace_row) {
            if (empty($trace_row['class'])) {
                return '::' . $trace_row['function'];
            }
            if ($trace_row['class'] !== __CLASS__) {
                return $trace_row['class'] . $trace_row['type'] . $trace_row['function'];
            }
        }
        return '::';
    }

    /**
     * @param \Exception $e
     * @return int|mixed
     */
    protected static function getExceptionLevel(\Exception $e) {
        if ($e instanceof Application\ErrorException) {
            return Level::fromString($e->getLevel());
        }
        return Level::ERROR;
    }

    /**
     * @param \Exception $e
     * @return string
     */
    protected static function formatExceptionMessage(\Exception $e) {
        return get_class($e) . ': ' . $e->getMessage() . ' at ' . preg_replace("#\r?\n#", "\n\t", trim($e->getTraceAsString()));
    }

    /**
     * @param $level
     * @return mixed
     */
    protected static function resolveLevel($level) {
        if (is_numeric($level)) {
            return Level::ensureIsValid($level);
        }
        return Level::fromString((string)$level);
    }

}
