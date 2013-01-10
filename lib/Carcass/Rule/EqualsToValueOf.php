<?php

namespace Carcass\Rule;

use Carcass\Field;

class EqualsToValueOf extends Base {

    protected $OtherField;

    protected $ERROR = 'values_do_not_match';

    public function __construct(Field\FieldInterface $OtherField) {
        $this->OtherField = $OtherField;
    }

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate(['value' => $Field->getValue(), 'expected' => $this->OtherField->getValue()]);
    }

    public function validate($values) {
        return $values['value'] === $values['expected'];
    }
}
