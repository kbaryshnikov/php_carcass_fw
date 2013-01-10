<?php

namespace Carcass\Rule;

class IsMore extends Base {

    protected $max_value;

    protected $ERROR = 'too_small';

    public function __construct($max_value) {
        $this->max_value = $max_value;
    }

    public function validate($value) {
        return (null === $value || $value > $this->max_value);
    }
}
