<?php

namespace Carcass\Rule;

class EqualsTo extends Base {

    protected
        $ERROR = 'wrong_value',
        $correct_values;

    public function __construct($correct_values) {
        $this->correct_values = (array)$correct_values;
    }

    public function validate($value) {
        return (null === $value || in_array($value, $this->correct_values));
    }
}
