<?php

namespace Carcass\Memcached;

class Key {

    private function __construct() {}

    public static function create($template, array $config = []) {
        $Builder = new KeyBuilder($template, $config);
        return function(array $args = []) use ($Builder) {
            return $Builder->parse($args);
        };
    }

}
