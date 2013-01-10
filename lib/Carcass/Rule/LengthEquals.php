<?php

namespace Carcass\Rule;

class LengthEquals extends Base {

    protected $ERROR = 'invalid_length';
    protected $required_len;

    public function __construct($required_len) {
        $this->required_len = $required_len;
    } 

    public function validate($value) {
        if (null === $value) {
            return true;
        }
        $len = is_scalar($value) ? mb_strlen($value) : count($value);
        return $len == $this->required_len;
    }
}
