<?php

namespace Carcass\Rule;

use Carcass\Field;

class DateIsInRange extends Base {

    protected $ERROR = 'date_not_in_range';
    protected $date_min = null;
    protected $date_max = null;

    public function __construct($min = null, $max = null) {
        isset($min) and $this->date_min = is_integer($min) ? $min : strtotime($min);
        isset($max) and $this->date_max = is_integer($max) ? $max : strtotime($max);
    }

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate([
            'value' => $Field->getValue(),
            'min_value' => $Field->getMinValue(),
            'max_value' => $Field->getMaxValue()
        ]);
    }

    public function validate($values) {
        extract($values);

        if (null === $value || $value === Field\Base::INVALID_VALUE) {
            return false;
        }

        $min = $this->date_min === null ? $min_value : $this->date_min;
        $max = $this->date_max === null ? $max_value : $this->date_max;

        if ((null !== $min && $value < $min) || (null !== $max && $value > $max)) {
            return false;
        }

        return true;
    }
}
