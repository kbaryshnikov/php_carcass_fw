<?php

namespace Carcass\Application;

use Carcass\Corelib;

interface ControllerInterface {

    public function dispatch($action, Corelib\Hash $Args);

    public function dispatchNotFound($error_message);

}
