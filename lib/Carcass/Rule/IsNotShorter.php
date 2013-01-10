<?php

namespace Carcass\Rule;

class IsNotShorter extends Base {

    protected $ERROR = 'is_too_short';
    protected $min_len;

    public function __construct($min_len) {
        $this->min_len = $min_len;
    } 

    public function validate($value) {
        return (null === $value || mb_strlen($value) >= $this->min_len);
    }
}
