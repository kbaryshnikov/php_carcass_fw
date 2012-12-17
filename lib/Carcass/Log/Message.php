<?php

namespace Carcass\Log;

use Carcass\Corelib as Corelib;

class Message {

    const TIME_FORMAT = 'Y-m-d:H:i:s.u';

    protected
        $message,
        $level,
        $time;

    public function __construct($message, $level) {
        $this->message = trim($message, " \t\r\n");
        $this->level = $level;
        $this->time = Corelib\TimeTools::getMicrotime();
    }

    public function getMessage() {
        return $this->message;
    }

    public function getLevel() {
        return Level::toString($this->level);
    }

    public function getTime($format = null) {
        if (null === $format) {
            $format = self::TIME_FORMAT;
        }
        return Corelib\TimeTools::formatMicrotime($format, $this->time, true);
    }

    public function getRawLevel() {
        return $this->level;
    }

    public function getRawTime() {
        return $this->time;
    }

    public function getFormattedString() {
        return $this->getTime() . ' [' . $this->getLevel() . '] ' . $this->getMessage();
    }

    public function __toString() {
        return $this->getFormattedString();
    }

}
