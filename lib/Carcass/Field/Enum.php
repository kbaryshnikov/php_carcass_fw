<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

/**
 * Enum field
 *
 * @package Carcass\Field
 */
class Enum extends Base {

    protected $values = [];

    /**
     * @param array $values
     * @param $default_value
     */
    public function __construct(array $values = [], $default_value = null) {
        parent::__construct($default_value);
        $this->setEnumValues($values);
    }

    /**
     * @param array $values
     * @return $this
     */
    public function setEnumValues(array $values = []) {
        $this->values = $values;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEnumValues() {
        return $this->values;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        if (null !== $value && !is_scalar($value)) {
            $value = self::INVALID_VALUE;
        }
        parent::setValue($value);
        return $this;
    }

    /**
     * @return array
     */
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
