<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

use Carcass\Rule;
use Carcass\Filter;
use Carcass\Corelib;

/**
 * Fieldset: collection of fields
 *
 * @package Carcass\Field
 */
class Set extends Corelib\Hash implements FieldInterface {
    use FilterTrait, RuleTrait {
        RuleTrait::validate as validateOwnRules;
    }

    protected $is_dynamic = false;
    protected $own_error = null;

    /**
     * @param $value
     */
    public function __construct($value = null) {
        $value and $this->addFields($value);
    }

    /**
     * @param $value
     * @return $this
     */
    public static function constructDynamic($value = null) {
        /** @var Set $self */
        $self = new static($value);
        return $self->setDynamic();
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function setDynamic($bool = true) {
        $this->is_dynamic = (bool)$bool;
        return $this;
    }

    /**
     * @param array $name_type_map
     * @return $this
     */
    public function castMulti(array $name_type_map) {
        foreach ($name_type_map as $name => $type) {
            $this->cast($name, $type);
        }
        return $this;
    }

    /**
     * @param callable $fn
     * @return $this
     * @throws \Exception
     */
    public function dynamic(Callable $fn) {
        $old_dynamic = $this->is_dynamic;
        $this->is_dynamic = true;
        try {
            $fn($this);
        } catch (\Exception $e) {
            // pass
        }
        // finally:
        $this->is_dynamic = $old_dynamic;
        if (isset($e)) {
            throw $e;
        }
        return $this;
    }

    /**
     * @param $name
     * @param $type
     * @return $this
     */
    public function cast($name, $type) {
        $value = $this->$name;
        $Field = $type instanceof FieldInterface ? $type : Base::factory($type);
        $Field->setValue($value);
        $this->doSet($name, $Field);
        return $this;
    }

    /**
     * @param $fields
     * @return $this
     * @throws \InvalidArgumentException
     */
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
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        return $this->merge($value);
    }

    /**
     * @param array|\Traversable $value
     * @return $this
     */
    public function merge($value) {
        return parent::merge($this->filterValue($value));
    }

    /**
     * @param array|\Traversable $value
     * @param bool $no_overwrite
     * @return $this
     */
    public function import($value, $no_overwrite = false) {
        $value = $this->filterValue($value);
        if (!$no_overwrite) {
            return $this->merge($value);
        }
        foreach ($value as $key => $item) {
            if (!$this->has($key)) {
                $this->set($key, $item);
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function getValue() {
        return $this;
    }

    /**
     * @return string
     */
    public function __toString() {
        return Corelib\JsonTools::encode($this->exportArray());
    }

    /**
     * @return $this
     */
    public function clear() {
        $this->rules = [];
        $this->filters = [];
        return parent::clear();
    }

    /**
     * @return $this
     */
    public function clean() {
        foreach ($this as $Field) {
            /** @var FieldInterface $Field */
            $Field->setValue(null);
        }
        return $this;
    }

    /**
     * @param $name
     * @param bool $throw_exception_on_missing_field
     * @return FieldInterface
     * @throws \InvalidArgumentException
     */
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

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function getFieldValue($name, $default = null) {
        $Field = $this->getField($name, false);
        return $Field !== null ? $Field->getValue() : $default;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->getField($name)->getValue();
    }

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->getField($name)->setValue($value);
    }

    /**
     * @param mixed $name
     * @param mixed $value
     * @return bool
     */
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

    /**
     * @param $name
     * @param null $value
     * @return FieldInterface
     */
    protected function autoCreateField($name, $value = null) {
        $Field = new Variant($value);
        parent::doSet($name, $Field);
        return $Field;
    }

    /**
     * @param array $rules_map
     * @return $this
     */
    public function setRules(array $rules_map) {
        foreach ($rules_map as $name => $rules) {
            $this->getField($name)->setRules(is_array($rules) ? $rules : [$rules]);
        }
        $this->taint();
        return $this;
    }

    /**
     * @param array $filters_map
     * @return $this
     */
    public function setFilters(array $filters_map) {
        foreach ($filters_map as $name => $filters) {
            $this->getField($name)->setFilters(is_array($filters) ? $filters : [$filters]);
        }
        $this->taint();
        return $this;
    }

    /**
     * @return array
     */
    public function exportArray() {
        $result = [];
        foreach ($this as $name => $Field) {
            /** @var FieldInterface $Field */
            $value = $Field->getValue();
            if ($value instanceof self) {
                $value = $value->exportArray();
            }
            $result[$name] = $value;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getRenderArray() {
        $result = [];
        foreach ($this as $name => $Field) {
            /** @var FieldInterface $Field */
            $result[$name] = $Field->getRenderArray();
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function validate() {
        if ($this->isTainted()) {
            $this->doValidate();
        }
        return $this->error === null;
    }

    public function setFieldError($field_name, $error) {
        $this->getField($field_name)->setError($error);
        $this->updateErrors();
        return $this;
    }

    protected function doValidate() {
        $this->error = null;
        $this->own_error = null;
        $this->validateOwnRules();
        if ($this->error !== null && !is_array($this->error)) {
            $this->own_error = $this->error;
            $this->error = ['_self_' => $this->error];
        }
        foreach ($this as $name => $Field) {
            /** @var FieldInterface $Field */
            if (!$Field->validate()) {
                $this->error[$name] = $Field->getError();
            }
        }
    }

    protected function updateErrors() {
        $this->error = null;
        if ($this->own_error) {
            $this->error = ['_self_' => $this->own_error];
        }
        foreach ($this as $name => $Field) {
            /** @var FieldInterface $Field */
            if (null !== $error = $Field->getError()) {
                $this->error[$name] = $error;
            }
        }
    }

}
