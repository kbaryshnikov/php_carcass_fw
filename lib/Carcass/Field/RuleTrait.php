<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

use Carcass\Rule;

/**
 * Rule methods implementation for fields
 *
 * @package Carcass\Field
 */
trait RuleTrait {

    protected $error = null;
    protected $rules = [];

    /**
     * @return $this
     */
    public function clearRules() {
        $this->rules = [];
        $this->error = null;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getError() {
        return $this->error;
    }

    /**
     * @param $error
     * @return $this
     */
    public function setError($error) {
        $this->error = $error;
        return $this;
    }

    /**
     * @param $rule
     * @return $this
     */
    public function addRule($rule) {
        if (!$rule instanceof Rule\RuleInterface) {
            $rule = Rule\Base::factory((array)$rule);
        }
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * @param array $rules
     * @return $this
     */
    public function setRules(array $rules) {
        foreach ($rules as $Rule) {
            $this->addRule($Rule);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function validate() {
        $this->error = null;
        foreach ($this->rules as $Rule) {
            /** @var Rule\RuleInterface $Rule */
            /** @noinspection PhpParamsInspection */
            $Rule->validateField($this);
            if (null !== $this->error) {
                break;
            }
        }
        return null === $this->error;
    }

}
