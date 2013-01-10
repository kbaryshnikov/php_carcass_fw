<?php

namespace Carcass\Field;

class Boolean extends Base {

    public function setValue($value) {
        $this->value = (bool) $value;
        return $this;
    }

    public function getValue() {
        return $this->value;
    }

    public function exportRenderArray() {
        $result = [
            'value' => $this->value,
            'attributes' => $this->attributes,
        ];

        return $result;
    }

}
