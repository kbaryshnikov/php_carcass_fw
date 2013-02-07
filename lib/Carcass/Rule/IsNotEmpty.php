<?php

namespace Carcass\Rule;

class IsNotEmpty extends IsEmpty {

    protected $ERROR = 'is_empty';

    public function validate($value) {
        return $value === null || !parent::validate($value);
    }
}
