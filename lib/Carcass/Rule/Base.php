<?php

namespace Carcass\Rule;

use Carcass\Field;

class Base implements RuleInterface {

    protected $ERROR = '@undefined@';

    /**
     * @return bool
     */
    public function validateField(Field\FieldInterface $Field) {
        $result = $this->validateFieldValue($Field);
        if ($result === false) {
            $Field->setError($this->getErrorName());
        }
        return $result;
    }

    public function validateValue($value) {
        return false;
    }

    public function getErrorName() {
        return $this->ERROR;
    }

    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate($Field->getValue());
    }

}

