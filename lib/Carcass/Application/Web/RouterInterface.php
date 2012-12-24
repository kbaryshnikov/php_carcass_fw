<?php

namespace Carcass\Application;

use Carcass\Corelib as Corelib;

interface Web_RouterInterface extends RouterInterface {

    public function getUrl(Corelib\Request $Request, $route, array $args);

    public function getAbsoluteUrl(Corelib\Request $Request, $route, array $args);

}
