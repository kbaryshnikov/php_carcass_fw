<?php

namespace Carcass\Field;

use Carcass\Corelib;
use Carcass\Rule;
use Carcass\Filter;

interface FieldInterface extends Corelib\ExportableInterface, Corelib\RenderableInterface {

    public function setValue($value);

    public function getValue();

    public function __toString();

    public function addRule($rule);

    public function setRules(array $rules);

    public function addFilter($filter);

    public function setFilters(array $filters);

    public function getRenderArray();

}
