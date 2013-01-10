<?php

namespace Carcass\Field;

class Scalar extends Base {

    public function setValue($value) {
        if (null !== $value && !is_scalar($value)) {
            $value = self::INVALID_VALUE;
        }
        parent::setValue($value);
        return $this;
    }

}
