<?php

namespace Carcass\Rule;

class IsArray extends Base {

    protected $ERROR = 'is_not_array';

    public function validate($value) {
        return (null === $value || is_array($value));
    }
}
