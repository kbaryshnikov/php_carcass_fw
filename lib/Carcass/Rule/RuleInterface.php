<?php

namespace Carcass\Rule;

use Carcass\Field;

interface RuleInterface {

    public function validateField(Field\FieldInterface $Field);

    public function validate($value);

    public function getErrorName();

}
