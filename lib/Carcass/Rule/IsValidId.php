<?php

namespace Carcass\Rule;

class IsValidId extends Base {

    protected $ERROR = 'is_not_id';

    public function validate($value) {
        if (null === $value) {
            return true;
        }
        return ( is_int($value) || ctype_digit($value) ) && $value > 0;
    }
}
