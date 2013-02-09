<?php

namespace Carcass\Field;

use Carcass\Rule;
use Carcass\Filter;
use Carcass\Corelib;

class Set extends Corelib\Hash implements FieldInterface {
    use Corelib\RenderableTrait, FilterTrait, RuleTrait {
        RuleTrait::validate as validateOwnRules;
    }

    protected $is_dynamic = false;

    public function __construct($value = null) {
        $value and $this->addFields($value);
    }

    public static function constructDynamic($value = null) {
        $self = new static($value);
        return $self->setDynamic();
    }

    public function setDynamic($bool = true) {
        $this->is_dynamic = (bool)$bool;
        return $this;
    }

    public function castMulti(array $name_type_map) {
        foreach ($name_type_map as $name => $type) {
            $this->cast($name, $type);
        }
        return $this;
    }

    public function cast($name, $type) {
        $value = $this->$name;
        $Field = $type instanceof FieldInterface ? $type : Base::factory($type);
        $Field->setValue($value);
        $this->doSet($name, $Field);
        return $this;
    }

    public function addFields($fields) {
        if (!Corelib\ArrayTools::isTraversable($fields)) {
            throw new \InvalidArgumentException('$fields must be traversable');
        }
        foreach ($fields as $key => &$value) {
            if (!$value instanceof FieldInterface) {
                if (is_string($value)) {
                    $ctor = [$value];
                } elseif (is_array($value)) {
                    $ctor = $value;
                } else {
                    throw new \InvalidArgumentException("Invalid value for '$key' field");
                }
                $value = Base::factory($ctor);
            }
        }
        $this->merge($fields);
    }

    public function setValue($value) {
        return $this->merge($value);
    }

    public function merge($value) {
        return parent::merge($this->filterValue($value));
    }

    public function import($value) {
        return $this->merge($value);
    }

    public function getValue() {
        return $this;
    }

    public function __toString() {
        return Corelib\ArrayTools::jsonEncode($this->exportArray());
    }

    public function clear() {
        $this->rules = [];
        $this->filters = [];
        return parent::clear();
    }

    public function getField($name, $throw_exception_on_missing_field = true) {
        $Field = $this->get($name);
        if (null === $Field) {
            if ($this->is_dynamic) {
                $Field = $this->autoCreateField($name);
            } elseif ($throw_exception_on_missing_field) {
                throw new \InvalidArgumentException("No '$name' field is registered");
            } else {
                return null;
            }
        }
        return $Field;
    }

    public function getFieldValue($name, $default = null) {
        $Field = $this->getField($name, false);
        return $Field !== null ? $Field->getValue() : $default;
    }

    public function __get($name) {
        return $this->getField($name)->getValue();
    }

    public function __set($name, $value) {
        $this->getField($name)->setValue($value);
    }

    protected function doSet($name, $value) {
        if ($this->is_dynamic && !$this->has($name)) {
            $this->autoCreateField($name);
        }
        if ($value instanceof FieldInterface) {
            return parent::doSet($name, $value);
        } elseif ($this->has($name)) {
            $this->get($name)->setValue($value);
            return true;
        }
        return false;
    }

    protected function autoCreateField($name, $value = null) {
        $Field = new Variant($value);
        parent::doSet($name, $Field);
        return $Field;
    }

    public function setRules(array $rules_map) {
        foreach ($rules_map as $name => $rules) {
            $this->getField($name)->setRules(is_array($rules) ? $rules : [$rules]);
        }
        return $this;
    }

    public function setFilters(array $filters_map) {
        foreach ($filters_map as $name => $filters) {
            $this->getField($name)->setFilters(is_array($filters) ? $filters : [$filters]);
        }
        return $this;
    }

    public function exportArray() {
        $result = [];
        foreach ($this as $name => $Field) {
            $value = $Field->getValue();
            if ($value instanceof self) {
                $value = $value->exportArray();
            }
            $result[$name] = $value;
        }
        return $result;
    }

    public function getRenderArray() {
        $result = [];
        foreach ($this as $name => $Field) {
            $result[$name] = $Field->getRenderArray();
        }
        return $result;
    }

    public function validate() {
        $this->error = null;
        $this->validateOwnRules();
        if ($this->error !== null && !is_array($this->error)) {
            $this->error = ['_self_' => $this->error];
        }
        foreach ($this as $name => $Field) {
            if (!$Field->validate()) {
                $this->error[$name] = $Field->getError();
            }
        }
        return $this->error === null;
    }

}
