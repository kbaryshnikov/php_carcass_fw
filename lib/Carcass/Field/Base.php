<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

use Carcass\Corelib;

/**
 * Abstract field
 *
 * @package Carcass\Field
 */
abstract class Base implements FieldInterface {
    use Corelib\RenderableTrait, RuleTrait, FilterTrait;

    const INVALID_VALUE = INF;

    protected $value;
    protected $attributes = [];

    /**
     * @param array|string $args
     * @return FieldInterface
     * @throws \InvalidArgumentException
     */
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

    /**
     * @param $default_value
     */
    public function __construct($default_value = null) {
        $this->setValue($default_value);
    }

    /**
     * @return bool
     */
    public function isValidValue() {
        return $this->value !== self::INVALID_VALUE;
    }

    /**
     * @return $this
     */
    public function clear() {
        $this->value = null;
        $this->clearRules();
        $this->clearFilters();
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        $this->value = $this->filterValue($value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes) {
        $this->attributes = $attributes + $this->attributes;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return null
     */
    public function getAttribute($key, $default = null) {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : $default;
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string)$this->getValue();
    }

    /**
     * @return array
     */
    public function exportArray() {
        $result = [
            'value' => $this->getValue(),
            'error' => $this->getError(),
            'Attributes' => $this->attributes,
        ];

        return $result;
    }

    /**
     * @return array
     */
    public function getRenderArray() {
        return $this->exportArray();
    }

}
