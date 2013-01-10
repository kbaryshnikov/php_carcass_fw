<?php

namespace Carcass\Field;

class Id extends Base {

    public function setValue($value) {
        $this->value = number_format($value, 0, '', '');
        if ($this->value < 1) {
            $this->value = null;
        }
        return $this;
    }

}
