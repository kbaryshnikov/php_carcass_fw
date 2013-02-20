<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

/**
 * Multiselect field
 * @package Carcass\Field
 */
class Multiselect extends Base {

    protected $values;

    /**
     * @param array $values
     * @param array $default_values
     */
    public function __construct(array $values = [], array $default_values = null) {
        parent::__construct($default_values);
        $this->setMultiselectValues($values);
    }

    /**
     * @return $this
     */
    public function clear() {
        $this->values = null;
        return $this;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function setMultiselectValues(array $values = []) {
        $this->values = $values;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMultiselectValues() {
        return $this->values;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        if (!is_array($value)) {
            $value = [];
        }
        $set_value = [];
        foreach ($value as $k => $v) {
            if (is_scalar($v) && strlen($v)) {
                $set_value[] = $v;
            }
        }
        parent::setValue($set_value);
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
                'selected'  => in_array((string)$k, array_map('strval', $this->value), true),
            ];
        }

        $set['Values'] = $set_values;

        return $set;
    }

}
