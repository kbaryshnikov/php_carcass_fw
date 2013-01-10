<?php

namespace Carcass\Filter;

class NullifyEmpty implements FilterInterface {

    public function filter(&$value) {
        if (is_scalar($value)) {
            if (!strlen($value)) {
                $value = null;
            }
        } elseif (is_array($value)) {
            if (!count($value)) {
                $value = null;
            }
        } else {
            if (empty($value)) {
                $value = null;
            }
        }
    }

}
