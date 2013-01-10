<?php

namespace Carcass\Filter;

class Intval implements FilterInterface {

    public function filter(&$value) {
        if (!is_int($value) || !ctype_digit($value) || !(substr($value, 0, 1) == '-' && ctype_digit(substr($value, 1)))) {
            $value = intval($value);
        }
    }

}
