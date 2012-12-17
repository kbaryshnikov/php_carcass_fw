<?php

namespace Carcass\Corelib;

class ArrayTools {

    public static function isTraversable($var) {
        return is_array($var) || $var instanceof \Traversable;
    }

    /**
     * Recursively merge arrays by keys with filter
     *
     * @param array &$a1
     * @param array $a2
     * @param array $unset_values remove $a1 item if the same $a2 item strictly strictly exists in $unset_values
     * @param bool $replace overwrite existing
     * @return void
     */
    static public function mergeInto(array &$a1, array $a2, array $unset_values = array(), $replace = false) {
        foreach ($a2 as $k => $v) {
            if (count($unset_values) && in_array($v, $unset_values, true)) {
                unset($a1[$k]);
                continue;
            }
            if (!array_key_exists($k, $a1)) {
                $a1[$k] = $v;
            } else {
                if (is_array($a2[$k]) && is_array($a1[$k])) {
                    self::mergeInto($a1[$k], $a2[$k], $unset_values, $replace);
                } else {
                    if ($replace) {
                        $a1[$k] = $a2[$k];
                    }
                }
            }
        }
    }

}
