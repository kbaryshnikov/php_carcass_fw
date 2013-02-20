<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Collection of date/time-related tools
 *
 * @package Carcass\Corelib
 */
class TimeTools {

    /**
     * @var int|null offset (for tests)
     */
    private static $offset = null;

    /**
     * Sets time offset: for tests only!
     */
    public static function setTimeOffset($offset) {
        self::$offset = $offset;
    }

    /**
     * @return int|null
     */
    public static function getTimeOffset() {
        return self::$offset;
    }

    /**
     * @return int
     */
    public static function getTime() {
        $time = time();
        return isset(self::$offset) ? $time + self::$offset : $time;
    }

    /**
     * @return float
     */
    public static function getMicrotime() {
        $time = microtime(true);
        return isset(self::$offset) ? $time + self::$offset : $time;
    }

    /**
     * @param int $m
     * @return int
     */
    public static function minutes($m) {
        return intval($m) * 60;
    }

    /**
     * @param int $h
     * @return int
     */
    public static function hours($h) {
        return intval($h) * 3600;
    }

    /**
     * @param int $h
     * @return int
     */
    public static function days($h) {
        return intval($h) * 3600 * 24;
    }

    /**
     * Time formatter. Returns an UTC datetime string
     *
     * @param string $format format string, according to php date
     * @param float|null $time defaults to now
     * @return string
     */
    public static function formatTime($format, $time = null) {
        return self::_formatTime($format, $time, false);
    }

    /**
     * Local time formatter. Returns a local datetime string.
     *
     * @param string $format format string, according to php date
     * @param float|null $time defaults to now
     * @return string
     */
    public static function formatLocalTime($format, $time = null) {
        return self::_formatTime($format, $time, true);
    }

    /**
     * @param $format
     * @param null $time
     * @param bool $as_local_datetime
     * @return string
     */
    private static function _formatTime($format, $time = null, $as_local_datetime = false) {
        if (null === $time) {
            $time = self::getTime();
        }
        return $as_local_datetime
            ? date($format, $time)
            : gmdate($format, $time);
    }

    /**
     * Microtime formatter
     *
     * @param string $format format string, according to php date
     * @param float|null $microtime defaults to now
     * @param bool $as_local_datetime default false (GMT)
     * @return string
     */
    public static function formatMicrotime($format, $microtime = null, $as_local_datetime = false) {
        if (null == $microtime) {
            $microtime = self::getMicrotime();
        }
        $timestamp = floor($microtime);
        $milliseconds = round(($microtime - $timestamp) * 1000000);
        return $as_local_datetime
            ? date  (preg_replace('#(?<!\\\\)u#', $milliseconds, $format), $timestamp)
            : gmdate(preg_replace('#(?<!\\\\)u#', $milliseconds, $format), $timestamp);
    }

    /**
     * Converts a SQL date to unix timestamp
     *
     * @param string $sql_date_str
     * @return integer
     */
    public static function sqlDateToTimestamp($sql_date_str) {
        return strtotime("${sql_date_str} 00:00:00 GMT");
    }

    /**
     * Converts a SQL datetime to unix timestamp
     *
     * @param string $sql_datetime_str
     * @return integer
     */
    public static function sqlDatetimeToTimestamp($sql_datetime_str) {
        return strtotime("$sql_datetime_str GMT");
    }

}
