<?php

namespace Carcass\Log;

class Level {

    const
        ERROR         = 4,
        WARNING       = 3,
        NOTICE        = 2,
        DEBUG         = 1,
        DEBUG_VERBOSE = 0;

    private static $level_texts = [
        self::ERROR           => 'Error',
        self::WARNING         => 'Warning',
        self::NOTICE          => 'Notice',
        self::DEBUG           => 'Debug',
        self::DEBUG_VERBOSE   => 'DebugVerbose',
    ];

    public static function ensureIsValid($level) {
        if (!isset(self::$level_texts[$level])) {
            throw new \InvalidArgumentException("Unknown log level: '$level'");
        }
        return $level;
    }

    public static function fromString($level_text) {
        $level = array_search(ucfirst($level_text), self::$level_texts, true);
        if (false === $level) {
            throw new \InvalidArgumentException("Unknown log level: '$level_text'");
        }
        return $level;
    }

    public static function toString($level) {
        self::ensureIsValid($level);
        return self::$level_texts[$level];
    }

}
