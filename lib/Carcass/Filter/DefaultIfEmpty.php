<?php

namespace Carcass\Filter;

class DefaultIfEmpty implements FilterInterface {

    protected $default_value = null;

    public function __construct($default_value = null) {
        $this->default_value = $default_value;
    }

    public function filter(&$value) {
        if (empty($value)) {
            $value = $this->default_value;
        }
    }

}
