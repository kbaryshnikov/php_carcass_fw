<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Collection of array tools
 *
 * @package Carcass\Corelib
 */
class ArrayTools {

    /**
     * @param mixed $var
     * @return bool
     */
    public static function isTraversable($var) {
        return is_array($var) || $var instanceof \Traversable;
    }

    /**
     * @param mixed $var
     * @return bool
     */
    public static function isArrayAccessable($var) {
        return is_array($var) || $var instanceof \ArrayAccess;
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
    public static function mergeInto(array &$a1, array $a2, array $unset_values = [], $replace = false) {
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

    /**
     * Saves contents of $data as a valid PHP code which defines $variable_name to $filename
     *
     * @param mixed $filename full pathname to genertated file
     * @param mixed $variable_name the name of variable which will be assigned
     * @param mixed $data
     * @param mixed $auto_create_dirs automatically create required directories recursively
     * @return void
     */
    public static function exportToFile($filename, $variable_name, $data, $auto_create_dirs = false) {
        if ($auto_create_dirs && !file_exists($dir = dirname($filename))) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(
            $filename,
            '<?php '
                . ( empty($variable_name) ? 'return ' : ('${\'' . addcslashes((string)$variable_name, "\'\\") . '\'}=') )
                . var_export($data, true) . ';',
            LOCK_EX
        );
    }

    /**
     * Filters an associative array.
     * 
     * @param array    $array            Array to filter 
     * @param Callable $filter_function  ($key, $value) must return a new key (or true to leave the old key), or false/null to skip the item
     * @return array Filtered array
     */
    public static function filterAssoc(array $array, Callable $filter_function) {
        $result = [];
        foreach ($array as $key => $value) {
            if ($new_key = $filter_function($key, $value)) {
                if ($new_key === true) {
                    $new_key = $key;
                }
                $result[$new_key] = $value;
            }
        }
        return $result;
    }

    /**
     * Executes map function on associative array.
     *
     * @param array    $array            Array to map
     * @param callable $map_function     fn(&$key, $value) returns new value, can modify key
     * @return array Filtered array
     */
    public static function mapAssoc(array $array, Callable $map_function) {
        $result = [];
        foreach ($array as $key => $value) {
            $value = $map_function($key, $value);
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Resursive implode
     * 
     * @param string $separator 
     * @param array $items 
     * @return string
     */
    public static function joinRecursive($separator, array $items) {
        return join($separator, array_map(function($item) use ($separator) {
            return is_array($item) ? ArrayTools::joinRecursive($separator, $item) : $item;
        }, $items));
    }

    /**
     * packIntegers 
     * 
     * @param array $integers 
     * @param string $pack_mode default 'V' pack argument
     * @return string
     */
    public static function packIntegers(array $integers, $pack_mode = 'V') {
        $len = count($integers);
        return $len
            ? call_user_func_array('pack', array_merge(array(str_repeat($pack_mode, $len)), array_map('intval', $integers)))
            : '';
    }

}
