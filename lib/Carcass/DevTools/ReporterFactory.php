<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\DevTools;

/**
 * ReporterFactory
 * @package Carcass\DevTools
 */
class ReporterFactory {

    /**
     * @var array
     */
    private static $output_mode_to_implementation_map = [
        'stderr'    => ['ConsoleReporter', 'STDERR'],
        'console'   => ['ConsoleReporter', null],
        'firebug'   => ['FirebugReporter', null],
        'file'      => ['ConsoleReporter', null],
    ];

    /**
     * @return object
     */
    public static function assembleDefault() {
        return static::assembleByType('stderr');
    }

    /**
     * @param $reporter_type
     * @return object
     * @throws \LogicException
     */
    public static function assembleByType($reporter_type) {
        $mode    = strtok($reporter_type, ':');
        $in_arg  = strtok(null);

        if (!array_key_exists($mode, self::$output_mode_to_implementation_map)) {
            throw new \LogicException("Unknown output mode: '$mode'");
        }
        list($class_name, $arg) = self::$output_mode_to_implementation_map[$mode];
        if (!empty($in_arg)) {
            $arg = $in_arg;
        }
        $fq_class_name = __NAMESPACE__ . '\\' . $class_name;
        return null === $arg ? new $fq_class_name : new $fq_class_name($arg);
    }

}
