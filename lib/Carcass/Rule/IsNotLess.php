<?php

namespace Carcass\Rule;

class IsNotLess extends Base {

    protected $min_value;

    protected $ERROR = 'too_small';

    public function __construct($min_value) {
        $this->min_value = $min_value;
    }

    public function validate($value) {
        return (null === $value || $value >= $this->min_value);
    }
}
