<?php

namespace Carcass\Field;

use Carcass\Corelib;

abstract class Base implements FieldInterface {
    use Corelib\RenderableTrait, RuleTrait, FilterTrait;

    const
        INVALID_VALUE = INF;

    protected
        $value,
        $attributes = [];

    public static function factory($args) {
        if (!is_array($args)) {
            $args = [$args];
        }
        $type = (string)array_shift($args);
        if (!$type) {
            throw new \InvalidArgumentException("Missing field type");
        }
        $class = substr($type, 0, 1) == '\\' ? $type : __NAMESPACE__ . '\\' . ucfirst($type);
        return Corelib\ObjectTools::construct($class, $args);
    }

    public function __construct($default_value = null) {
        $this->setValue($default_value);
    }

    public function isValidValue() {
        return $this->value !== self::INVALID_VALUE;
    }

    public function clear() {
        $this->value = null;
        $this->clearRules();
        $this->clearFilters();
        return $this;
    }

    public function setValue($value) {
        $this->value = $this->filterValue($value);
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
            'error' => $this->getError(),
            'Attributes' => $this->attributes,
        ];

        return $result;
    }

    public function getRenderArray() {
        return $this->exportArray();
    }

}
