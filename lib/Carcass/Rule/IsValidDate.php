<?php

namespace Carcass\Rule;

class IsValidDate extends Base {

    protected $ERROR = 'invalid_date';

    public function validate($value) {
        return ($value === null || (is_int($value) || ctype_digit($value)));
    }

}
