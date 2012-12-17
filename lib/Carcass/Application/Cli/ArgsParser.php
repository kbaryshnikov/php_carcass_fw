<?php

namespace Carcass\Application;

class Cli_ArgsParser {

    public static function parse(array $args) {
        $result = [];
        foreach ($args as $arg) {
            if (preg_match('#^-(\w+)(?:=(.*))?$#', $arg, $matches)) {
                $name = $matches[1];
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
