<?php

namespace Carcass\Less;

class Formatter_Lessjs extends Formatter_Classic {
    public $disableSingle = true;
    public $breakSelectors = true;
    public $assignSeparator = ": ";
    public $selectorSeparator = ",";
}

