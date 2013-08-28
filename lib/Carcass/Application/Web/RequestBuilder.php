<?php

namespace Carcass\Application;

use Carcass\Corelib;

/**
 * RequestBuilder for Web Applications.
 * @package Carcass\Application
 */
class Web_RequestBuilder implements RequestBuilderInterface {

    /**
     * Builds Corelib\Requests with subitems:
     *  Args    - query string vars;
     *  Vars    - POST variables and uploaded files;
     *  Env     - server and application envirionment,
     *  Cookies - cookie vars.
     * @param array $app_env
     * @return \Carcass\Corelib\Request
     */
    public static function assembleRequest(array $app_env = []) {
        return new Corelib\Request([
            'Args'    => $_GET,
            'Vars'    => $_SERVER['REQUEST_METHOD'] === 'POST' ? ((empty($_FILES) ? [] : static::buildFiles($_FILES)) + $_POST) : [],
            'Env'     => static::setupWebEnv($_SERVER) + $app_env,
            'Cookies' => $_COOKIE,
        ]);
    }

    protected static function buildFiles(array $files) {
        $result = [];
        foreach ($files as $key => $value) {
            $value = static::buildFile($value);
            if ($value) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected static function buildFile(array $file) {
        static $ref_key = 'tmp_name';
        if (!isset($file[$ref_key])) {
            return null;
        }
        if (is_array($file[$ref_key])) {
            $result = [];
            foreach ($file as $key => $key_values) {
                foreach ($key_values as $file_no => $key_value) {
                    $result[$file_no][$key] = $key_value;
                }
            }
            return $result;
        } else {
            return $file;
        }
    }

    protected static function setupWebEnv(array $env) {
        $env['HOST'] = static::detectHostname($env);
        $env['SCHEME'] = (!empty($env['HTTPS']) && 0 != strcasecmp($env['HTTPS'], 'off') && 0 != strcasecmp($env['HTTPS'], 'http'))
            ? 'https' : 'http';
        $env['PORT'] = static::detectPort($env, $env['SCHEME']);
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
        return rtrim(strtolower($host), '.');
    }

    protected static function detectPort($env, $scheme) {
        if (empty($env['SERVER_PORT'])) {
            return null;
        }
        switch ($scheme) {
            case 'http':
                $default_proto_port = 80;
                break;
            case 'https':
                $default_proto_port = 443;
                break;
            default:
                $default_proto_port = null;
                break;
        }
        if ($default_proto_port !== null && $env['SERVER_PORT'] == $default_proto_port) {
            return null;
        }
        return $env['SERVER_PORT'];
    }

}
