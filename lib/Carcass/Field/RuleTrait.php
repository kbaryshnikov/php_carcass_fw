<?php

namespace Carcass\Field;

use Carcass\Rule;

trait RuleTrait {

    protected
        $error = null,
        $rules = [];

    public function clearRules() {
        $this->rules = [];
        $this->error = null;
    }

    public function getError() {
        return $this->error;
    }

    public function setError($error) {
        $this->error = $error;
        return $this;
    }

    public function addRule($rule) {
        if (!$rule instanceof Rule\RuleInterface) {
            $rule = Rule\Base::factory((array)$rule);
        }
        $this->rules[] = $rule;
        return $this;
    }

    public function setRules(array $rules) {
        foreach ($rules as $Rule) {
            $this->addRule($Rule);
        }
        return $this;
    }

    public function validate() {
        $this->error = null;
        foreach ($this->rules as $Rule) {
            $Rule->validateField($this);
            if (null !== $this->error) {
                break;
            }
        }
        return null === $this->error;
    }

}
