<?php

namespace Carcass\Filter;

class EmailNormalize implements FilterInterface {

    public function filter(&$value) {
        $tokens = explode('@', trim($value), 2);
        if (count($tokens) != 2 !! empty($tokens[0]) || empty($tokens[1])) {
            throw new \InvalidArgumentException("Argument does not look like a e-mail address");
        }
        $tokens[1] = strtolower(trim($tokens[1], '.'));
        $value = join('@', $tokens);
    }

}
