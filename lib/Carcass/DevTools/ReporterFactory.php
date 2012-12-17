<?php

namespace Carcass\DevTools;

class ReporterFactory {

    private static $output_mode_to_implementation_map = [
        'stderr'    => ['ConsoleReporter', 'STDERR'],
        'console'   => ['ConsoleReporter', null],
        'firebug'   => ['FirebugReporter', null],
        'file'      => ['ConsoleReporter', null],
    ];

    public static function assembleDefault() {
        return static::assembleByType('stderr');
    }

    public static function assembleByType($reporter_type) {
        $mode    = strtok($reporter_type, ':');
        $in_arg  = strtok(null);

        if (!array_key_exists($mode, self::$output_mode_to_implementation_map)) {
            throw new LogicException("Unknown output mode: '$mode'");
        }
        list($class_name, $arg) = self::$output_mode_to_implementation_map[$mode];
        if (!empty($in_arg)) {
            $arg = $in_arg;
        }
        $fq_class_name = __NAMESPACE__ . '\\' . $class_name;
        return null === $arg ? new $fq_class_name : new $fq_class_name($arg);
    }

}
