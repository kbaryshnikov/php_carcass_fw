<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Log;

/**
 * Log Level definitions
 * @package Carcass\Log
 */
class Level {

    const
        ERROR         = 4,
        WARNING       = 3,
        NOTICE        = 2,
        DEBUG         = 1,
        DEBUG_VERBOSE = 0;

    /**
     * @var array level numbers to texts map
     */
    private static $level_texts = [
        self::ERROR           => 'Error',
        self::WARNING         => 'Warning',
        self::NOTICE          => 'Notice',
        self::DEBUG           => 'Debug',
        self::DEBUG_VERBOSE   => 'DebugVerbose',
    ];

    /**
     * @param int $level
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function ensureIsValid($level) {
        if (!isset(self::$level_texts[$level])) {
            throw new \InvalidArgumentException("Unknown log level: '$level'");
        }
        return $level;
    }

    /**
     * @param string $level_text
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function fromString($level_text) {
        $level = array_search(ucfirst($level_text), self::$level_texts, true);
        if (false === $level) {
            throw new \InvalidArgumentException("Unknown log level: '$level_text'");
        }
        return $level;
    }

    /**
     * @param int $level
     * @return string
     */
    public static function toString($level) {
        self::ensureIsValid($level);
        return self::$level_texts[$level];
    }

}
