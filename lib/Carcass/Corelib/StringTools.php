<?php

namespace Carcass\Corelib;

class StringTools {

    public static function webSafeBase64Encode($s) {
        return strtr(rtrim(base64_encode($s), '='), '+/', '-_');
    }

    public static function webSafeBase64Decode($s) {
        return base64_decode(strtr($s, '-_', '+/'));
    }

    public static function jsonDecode($s) {
        return json_decode($s, true);
    }

    public static function unpackIntegers($packed, $pack_mode = 'V') {
        return strlen($packed) ? unpack($pack_mode . '*', $packed) : array();
    }

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
