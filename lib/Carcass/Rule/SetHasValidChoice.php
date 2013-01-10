<?php

namespace Carcass\Rule;

class SetHasValidChoice extends Base {

    protected $ERROR = 'invalid_choice';

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate(['selected_values' => $Field->getValue(), 'allowed_values' => $Field->getSetValues()]);
    }
    
    public function validate($values) {
        extract($values);
        if (null === $selected_values) {
            return true;
        }
        if (empty($allowed_values)) {
            return false;
        }
        foreach ($selected_values as $selected) {
            if (!is_scalar($selected) || !isset($allowed_values[$selected])) {
                return false;
            }
        }

        return true;
    }
}
