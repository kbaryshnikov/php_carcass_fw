<?php

namespace Carcass\Corelib;

if (class_exists('\\Blitz', false)) {

    class StringTemplate extends \Blitz {

        public static function constructFromFile($file) {
            return new static($file);
        }

        public static function constructFromString($string) {
            $self = new static;
            $self->load($string);
            return $self;
        }

        public static function parseString($string, array $args = []) {
            return static::constructFromString($string)->parse($args);
        }

    }

} else {

    class StringTemplate extends BlitzLiteEmulator {}

}
