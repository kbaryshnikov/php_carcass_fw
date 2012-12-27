<?php

namespace Carcass\Application;

use Carcass\Corelib;

interface RouterInterface {

    public function route(Corelib\Request $Request, ControllerInterface $Controller);

}
