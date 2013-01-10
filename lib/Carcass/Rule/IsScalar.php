<?php

namespace Carcass\Rule;

class IsScalar extends Base {

    protected $ERROR = 'is_not_scalar';

    public function validate($value) {
        return (null === $value || is_scalar($value));
    }

}
