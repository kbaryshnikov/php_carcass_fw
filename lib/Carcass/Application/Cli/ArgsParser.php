<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

/**
 * Cli appication arguments parser.
 *
 * Supported arguments syntax:
 *   -b              [ 'b' => true ]
 *   -boolean        [ 'boolean' => true ]
 *   -string=value   [ 'string' => 'value ]
 *   foo             [ 0 => 'foo' ]
 *   --              no more named arguments
 *   -bar            [ 1 => 'bar ']
 *
 * @package Carcass\Application
 */
class Cli_ArgsParser {

    /**
     * @param array $args raw arguments ('argv')
     * @return array parsed arguments
     */
    public static function parse(array $args) {
        $result     = [];
        $parse_opts = true;
        foreach ($args as $arg) {
            if ($parse_opts && $arg === '--') {
                $parse_opts = false;
                continue;
            }
            if ($parse_opts && preg_match('#^-(\w+)(?:=(.*))?$#', $arg, $matches)) {
                $name  = $matches[1];
                $value = isset($matches[2]) ? strval($matches[2]) : true;
                if (isset($result[$name])) {
                    if (!is_array($result[$name])) {
                        $result[$name] = [$result[$name]];
                    }
                    $result[$name][] = $value;
                } else {
                    $result[$name] = $value;
                }
            } else {
                $result[] = $arg;
            }
        }
        return $result;
    }

}
