<?php

namespace Carcass\Field;

class Enum extends Base {

    protected $values;

    public function __construct(array $values = [], $default_value = null) {
        parent::__construct($default_value);
        $this->setEnumValues($values);
    }

    public function setEnumValues(array $values = []) {
        $this->values = $values;
        return $this;
    }

    public function getEnumValues() {
        return $this->values;
    }

    public function setValue($value) {
        if (null !== $value && !is_scalar($value)) {
            $value = self::INVALID_VALUE;
        }
        parent::setValue($value);
        return $this;
    }

    public function exportArray() {
        $set = parent::exportArray();

        $set_values = [];

        foreach ($this->values as $k => $v) {
            $set_values[] = [
                'key'       => $k,
                'value'     => $v,
                'selected'  => !strcmp($k, $this->value),
            ];
        }

        $set['Values'] = $set_values;

        return $set;
    }

}
