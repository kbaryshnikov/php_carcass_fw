<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Cli_RequestBuilder {

    public static function assembleRequest() {
        if (!empty($_SERVER['argv']) && count($_SERVER['argv']) > 1) {
            $args = Cli_ArgsParser::parse(array_slice($_SERVER['argv'], 1));
        } else {
            $args = [];
        }
        return new Request([
            'Args' => $args,
            'Env'  => $_SERVER,
        ]);
    }

}
