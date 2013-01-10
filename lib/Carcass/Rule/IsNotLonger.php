<?php

namespace Carcass\Rule;

class IsNotLonger extends Base {

    protected $ERROR = 'is_too_long';
    protected $max_len;

    public function __construct($max_len) {
        $this->max_len = $max_len;
    } 

    public function validate($value) {
        return (null === $value || mb_strlen($value) <= $this->max_len);
    }

}
