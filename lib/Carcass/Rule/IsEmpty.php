<?php

namespace Carcass\Rule;

class IsEmpty extends Base {

    protected $ERROR = 'is_not_empty';

    public function validate($value) {
        return (null === $value || false === $value || (is_array($value) && !count($value)) || (is_scalar($value) && !strlen($value)));
    }
}
