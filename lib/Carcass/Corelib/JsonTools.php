<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

use Carcass\Application;

/**
 * JSON tools
 *
 * @package Carcass\Corelib
 */
class JsonTools {

    /**
     * @var int
     */
    protected static $encode_flags = null;
    /**
     * @var int
     */
    protected static $decode_flags = null;

    /**
     * @param mixed $in_data
     * @param int|null $encode_flags
     * @return string
     */
    public static function encode($in_data, $encode_flags = null) {
        try {
            return json_encode($in_data, $encode_flags === null ? static::getEncodeFlags() : $encode_flags);
        } catch (Application\WarningException $e) {
            Application\DI::getDebugger()->dumpException($e);
            Application\DI::getDebugger()->dump($in_data);
            Application\DI::getLogger()->logException($e);
            Application\DI::getLogger()->logWarning($in_data);
            return 'null';
        }
    }

    /**
     * @param string $in_str
     * @param int|null $decode_flags
     * @param bool $as_object
     * @param int $depth
     * @return mixed
     */
    public static function decode($in_str, $decode_flags = null, $as_object = false, $depth = 512) {
        return json_decode($in_str, !$as_object, $depth, $decode_flags === null ? static::getDecodeFlags() : $decode_flags);
    }

    /**
     * @param string $in_str
     * @param int|null $decode_flags
     * @return mixed
     */
    public static function decodeAsObject($in_str, $decode_flags = null) {
        return static::decode($in_str, $decode_flags, true);
    }

    /**
     * @return int
     */
    protected static function getEncodeFlags() {
        if (null === static::$encode_flags) {
            static::$encode_flags = JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        }
        return static::$encode_flags;
    }

    /**
     * @param int|null $flags
     * @param bool $append
     */
    public static function setEncodeFlags($flags, $append = false) {
        static::$encode_flags = $flags === null ? null : ($append ? static::$encode_flags | (int)$flags : (int)$flags);
    }

    /**
     * @return int
     */
    protected static function getDecodeFlags() {
        if (null === static::$decode_flags) {
            static::$decode_flags = JSON_BIGINT_AS_STRING;
        }
        return static::$decode_flags;
    }

    /**
     * @param int|null $flags
     * @param bool $append
     */
    public static function setDecodeFlags($flags, $append = false) {
        static::$decode_flags = $flags === null ? null : ($append ? static::$decode_flags | (int)$flags : (int)$flags);
    }

}