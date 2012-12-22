<?php

namespace Carcass\Application;

use Carcass\Corelib as Corelib;

interface RouterInterface {

    public function route(Corelib\Request $Request, ControllerInterface $Controller);

}
