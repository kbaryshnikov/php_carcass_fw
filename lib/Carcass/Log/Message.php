<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Log;

use Carcass\Corelib;

/**
 * Log Message
 * @package Carcass\Log
 */
class Message {

    const TIME_FORMAT = 'Y-m-d:H:i:s.u';

    protected
        $message,
        $level,
        $time;

    /**
     * @param string $message
     * @param int $level
     */
    public function __construct($message, $level) {
        $this->message = trim($message, " \t\r\n");
        $this->level = $level;
        $this->time = Corelib\TimeTools::getMicrotime();
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getLevel() {
        return Level::toString($this->level);
    }

    /**
     * @param null $format
     * @return string
     */
    public function getTime($format = null) {
        if (null === $format) {
            $format = self::TIME_FORMAT;
        }
        return Corelib\TimeTools::formatMicrotime($format, $this->time, true);
    }

    /**
     * @return int
     */
    public function getRawLevel() {
        return $this->level;
    }

    /**
     * @return float
     */
    public function getRawTime() {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getFormattedString() {
        return $this->getTime() . ' [' . $this->getLevel() . '] ' . $this->getMessage();
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->getFormattedString();
    }

}
