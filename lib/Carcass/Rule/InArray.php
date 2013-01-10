<?php

namespace Carcass\Rule;

class InArray extends Base {

    protected
        $known_values,
        $strict = false;

    protected $ERROR = 'value_not_in_array';

    public function __construct(array $known_values, $strict = false) {
        $this->known_values = $known_values;
        $this->strict = $strict;
    }
    
    public function validate($value) {
        return null === $value || in_array($value, $this->known_values, $this->strict);
    }
}
