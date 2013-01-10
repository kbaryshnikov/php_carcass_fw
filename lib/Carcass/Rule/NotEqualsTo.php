<?php

namespace Carcass\Rule;

class NotEqualsTo extends Base {

    protected $ERROR = 'forbidden_value';
    protected $forbidden_values;

    /**
     * @param scalar|array $forbidden_values  value or array of values which are not acceptable
     */
    public function __construct($forbidden_values) {
        $this->forbidden_values = (array)$forbidden_values;
    }

    public function validate($value) {
        return (null === $value || !in_array($value, $this->forbidden_values, true));
    }

}
