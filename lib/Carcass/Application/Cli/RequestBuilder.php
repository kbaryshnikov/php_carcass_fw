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
        return new Corelib\Request([
            'Args' => $args,
            'Env'  => static::setupCliEnv($_SERVER),
        ]);
    }

    protected static function setupCliEnv(array $env) {
        $env['HOST'] = static::detectHostname($env);
        return $env;
    }

    protected static function detectHostname($env) {
        $host = null;
        if (!empty($env['HOST'])) {
            $host = $env['HOST'];
        } else {
            $host = php_uname('h');
        }
        return rtrim( strtolower($host), '.' );
    }

}
