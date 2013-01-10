<?php

namespace Carcass\Field;

use Carcass\Corelib;

interface FieldInterface extends Corelib\ExportableInterface {

    public function setValue($value);

    public function getValue();

    public function __toString();

    public function exportRenderArray();

}
