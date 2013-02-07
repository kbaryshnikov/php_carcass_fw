<?php

namespace Carcass\Rule;

use Carcass\Field;
use Carcass\Corelib;

class Base implements RuleInterface {

    protected $ERROR = '@undefined@';

    public static function factory(array $args) {
        $type = (string)array_shift($args);
        if (!$type) {
            throw new \InvalidArgumentException("Missing rule type");
        }
        $class = substr($type, 0, 1) == '\\' ? $type : __NAMESPACE__ . '\\' . ucfirst($type);
        return Corelib\ObjectTools::construct($class, $args);
    }

    public function validate($value) {
        throw new \LogicException('Not impemented');
    }

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

