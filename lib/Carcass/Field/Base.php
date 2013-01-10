<?php

namespace Carcass\Field;

abstract class Base implements FieldInterface {

    const
        INVALID_VALUE = NAN;

    protected
        $value,
        $attributes = array();

    public function __construct($default_value = null) {
        $this->setValue($default_value);
    }

    public function isValidValue() {
        return $this->value !== self::INVALID_VALUE;
    }

    public function clear() {
        $this->value = null;
        return $this;
    }

    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

    public function getValue() {
        return $this->value;
    }

    public function setAttributes(array $attributes) {
        $this->attributes = $attributes + $this->attributes;
        return $this;
    }

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute($key, $default = null) {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : $default;
    }

    public function __toString() {
        return (string)$this->getValue();
    }

    public function exportArray() {
        $result = [
            'value' => $this->getValue(),
            'Attributes' => $this->attributes,
        ];

        return $result;
    }

    public function exportRenderArray() {
        return $this->exportArray();
    }

}
