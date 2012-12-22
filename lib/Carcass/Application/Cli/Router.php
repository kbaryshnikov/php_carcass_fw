<?php

namespace Carcass\Application;

use Carcass\Corelib as Corelib;

class Cli_Router implements RouterInterface {

    public function route(Corelib\Request $Request, ControllerInterface $Controller) {
        $script_name = $Request->Args->get(0);
        if (!$script_name) {
            $Controller->dispatch('Default.Default', $Request->Args);
        } else {
            $Controller->dispatch(ucfirst($script_name), $Request->Args);
        }
    }

}
