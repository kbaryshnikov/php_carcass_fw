<?php

namespace Carcass\Filter;

class Fn implements FilterInterface {

    protected $fn;

    public function __construct(Callable $fn) {
        $this->fn = $fn;
    }

    public function filter(&$value) {
        $fn = $this->fn;
        $value = $fn($value);
    }

}
