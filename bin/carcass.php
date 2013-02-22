<?php

namespace Carcass\Tools;

use Carcass\Application\Instance;

if (!isset($_SERVER['CARCASS_ROOT'])) {
    $_SERVER['CARCASS_ROOT'] = dirname(dirname(__FILE__)) . '/lib';
}

require_once $_SERVER['CARCASS_ROOT'] . '/Carcass/Application/Instance.php';                                                                                               
Instance::run(__DIR__);
