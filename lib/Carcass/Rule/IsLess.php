<?php

namespace Carcass\Rule;

class IsLess extends Base {

    protected $min_value;

    protected $ERROR = 'too_large';

    public function __construct($min_value) {
        $this->min_value = $min_value;
    }

    public function validate($value) {
        return (null === $value || $value < $this->min_value);
    }
}
