<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Collection of string tools
 *
 * @package Carcass\Corelib
 */
class StringTools {

    /**
     * @param string $s
     * @return string modified Base64 for URL
     */
    public static function webSafeBase64Encode($s) {
        return strtr(rtrim(base64_encode($s), '='), '+/', '-_');
    }

    /**
     * @param string $s modified Base64 for URL
     * @return string
     */
    public static function webSafeBase64Decode($s) {
        return base64_decode(strtr($s, '-_', '+/'));
    }

    /**
     * @param string $s
     * @return mixed
     */
    public static function jsonDecode($s) {
        return json_decode($s, true);
    }

    /**
     * @param string $packed
     * @param string $pack_mode
     * @return array
     */
    public static function unpackIntegers($packed, $pack_mode = 'V') {
        return strlen($packed) ? unpack($pack_mode . '*', $packed) : array();
    }

    /**
     * @param string $template_string
     * @param array $args
     * @return mixed
     */
    public static function parseTemplate($template_string, array $args) {
        return StringTemplate::parseString($template_string, $args);
    }

    /**
     * Split $s with $separator to max $limit items, use $defaults for missing offsets.
     *
     * @param string $s
     * @param string $separator
     * @param array|int|null $limit If array, value is used for $defaults, and $limit is size of array
     * @param array $defaults
     * @return array
     */
    public static function split($s, $separator, $limit = null, array $defaults = []) {
        if (is_array($limit)) {
            $defaults = $limit;
            $limit = count($defaults);
        }
        if (null === $limit) {
            $tokens = explode($separator, $s);
        } else {
            $tokens = explode($separator, $s, $limit);
        }
        if (count($tokens) < count($defaults)) {
            for ($idx = count($tokens); $idx < count($defaults); ++$idx) {
                $tokens[$idx] = $defaults[$idx];
            }
        }
        return $tokens;
    }

}
