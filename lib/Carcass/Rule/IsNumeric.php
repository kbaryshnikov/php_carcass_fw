<?php

namespace Carcass\Rule;

class IsNumeric extends Base {

    protected $ERROR = 'is_not_numeric';
    protected $allow_negative = false;

    public function __construct($allow_negative = false) {
        $this->allow_negative = (bool)$allow_negative;
    }

    public function validate($value) {
        if (null === $value) {
            return true;
        }
        if (is_int($value)) {
            return $this->allow_negative ? true : $value >= 0;
        }
        if (ctype_digit($value)) {
            return true;
        }   
        if ($this->allow_negative && substr($value, 0, 1) == '-' && ctype_digit(substr($value, 1))) {
            return true;
        }
        return false;
    }
}
