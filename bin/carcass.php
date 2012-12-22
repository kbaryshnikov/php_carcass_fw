<?php

namespace Carcass\Tools;

if (!isset($_SERVER['CARCASS_ROOT'])) {
    $_SERVER['CARCASS_ROOT'] = dirname(dirname(__FILE__)) . '/lib';
}

require_once $_SERVER['CARCASS_ROOT'] . '/Carcass/Application/Instance.php';                                                                                               
\Carcass\Application\Instance::run(__DIR__);
