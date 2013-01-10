<?php

namespace Carcass\Filter;

class Trim implements FilterInterface {

    public function filter(&$value) {
        $value = trim($value);
    }

}
