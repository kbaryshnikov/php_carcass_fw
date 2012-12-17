<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_RequestBuilder {

    public static function assembleRequest() {
        return new Request([
            'Args'    => $_GET,
            'Vars'    => $_SERVER['REQUEST_METHOD'] === 'POST' ? ((empty($_FILES) ? [] : $_FILES) + $_POST) : [],
            'Env'     => $_SERVER,
            'Cookies' => $_COOKIE,
        ]);
    }

}
