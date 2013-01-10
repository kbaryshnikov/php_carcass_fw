<?php

namespace Carcass\Rule;

use Carcass\Field;

class EnumHasValidChoice extends Base {

    protected $ERROR = 'invalid_choice';

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate(['value' => $Field->getValue(), 'allowed_values' => $Field->getEnumValues()]);
    }
    
    public function validate($values) {
        extract($values);

        if (null === $value) {
            return true;
        }

        if (empty($allowed_values) || !array_key_exists($value, $allowed_values)) {
            return false;
        }

        return true;
    }
}
