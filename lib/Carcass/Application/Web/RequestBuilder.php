<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_RequestBuilder {

    public static function assembleRequest() {
        return new Corelib\Request([
            'Args'    => $_GET,
            'Vars'    => $_SERVER['REQUEST_METHOD'] === 'POST' ? ((empty($_FILES) ? [] : $_FILES) + $_POST) : [],
            'Env'     => static::setupWebEnv($_SERVER),
            'Cookies' => $_COOKIE,
        ]);
    }

    protected static function setupWebEnv(array $env) {
        $env['HOST'] = static::detectHostname($env);
        $env['SCHEME'] = (!empty($env['HTTPS']) && 0 != strcasecmp($env['HTTPS'], 'off')) ? 'https' : 'http';
        return $env;
    }

    protected static function detectHostname($env) {
        $host = null;
        if (!empty($env['HOST'])) {
            $host = $env['HOST'];
        } elseif (!empty($env['HTTP_HOST'])) {
            $host = $env['HTTP_HOST'];
        } elseif (!empty($env['SERVER_NAME'])) {
            $host = $env['SERVER_NAME'];
        } else {
            $host = php_uname('h');
        }
        return rtrim( strtolower($host), '.' );
    }

}
