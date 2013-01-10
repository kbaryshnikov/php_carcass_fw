<?php

namespace Carcass\Rule;

class IsFloat extends Base {

    protected $ERROR = 'is_not_float';

    public function validate($value) {
        if (null === $value) {
            return true;
        }
        return is_numeric($value);
    }

}
