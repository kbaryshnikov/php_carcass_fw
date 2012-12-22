<?php

namespace Carcass\Tools;

use Carcass\Corelib as Corelib;

class AppEnvReader {

    public function load($dir, $env_file = 'env.php') {
        var_dump("!");exit;
        $env_file = $dir . '/' . $env_file;
        $Result = new Corelib\Hash(include $env_file);
        return $Result;
    }

}
