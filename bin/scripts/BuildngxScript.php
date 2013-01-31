<?php

namespace Carcass\Tools;

use Carcass\Application as Application;
use Carcass\Corelib as Corelib;
use Carcass\Config as Config;

class BuildngxScript extends Application\Controller {

    public function actionDefault($Args) {
        if ($Args->get('h')) {
            $this->printHelp();
            return 0;
        }
        $target = $Args->get('o', null);
        $app_root = rtrim($Args->get('app-root', getcwd()), '/') . '/';
        $AppEnv = new Corelib\Hash(include "{$app_root}env.php");
        $Config = new Config\Reader($this->getConfigLocations($app_root, $AppEnv));
        $Config->addConfigVar('APP_ROOT', $app_root);
        try {
            $vars = $this->buildVars($Config);
            $vars['app_root'] = rtrim($app_root, '/');
        } catch (\Exception $e) {
            $this->Response->writeLn("Configuration failure: " . $e->getMessage());
            return 1;
        }
        $ngx_cfg = "{$app_root}config/nginx.conf";
        $ngx_fcgi_cfg = "{$app_root}config/nginx-fastcgi.conf";
        if (file_exists($ngx_fcgi_cfg)) {
            $vars['FASTCGI'] = $this->parse($ngx_fcgi_cfg, $vars);
        }
        if (!file_exists($ngx_cfg)) {
            $this->Response->writeErrorLn("File not found: '$ngx_cfg'");
            return 1;
        }
        $result = $this->parse($ngx_cfg, $vars);
        if ($target) {
            file_put_contents($target, $result, LOCK_EX);
        } else {
            $this->Response->write($result);
        }
        return 0;
    }

    protected function parse($file, array $args) {
        return Corelib\StringTemplate::constructFromFile($file)->parse($args);
    }

    protected function buildVars($Config) {
        $vars = [
            'app_name' => $Config->application->name,
        ];
        foreach ($this->Request->Env->exportArray() as $k => $v) {
            $vars["ENV_$k"] = $v;
        }

        $vars['fcgi_addr'] = $Config->web->server->socket;

        if ($Config->web->server->has('listen')) {
            $vars['listen'] = self::assocToValArray($Config->web->server->listen);
        }

        $server_names = [];
        if ($Config->web->site->has('domain')) {
            $server_names[] = $Config->web->site->domain;
        }
        if ($Config->web->site->has('aliases')) {
            foreach ($Config->web->site->aliases as $alias) {
                $server_names[] = $alias;
            }
        }
        $vars['server_names'] = $server_names ? join(' ', $server_names) : null;

        if ($Config->web->server->has('ssl')) {
            $vars['ssl'] = static::assocToValArray($Config->web->server->ssl);
        }

        if ($Config->web->server->has('realip')) {
            if ($Config->web->server->realip->has('header')) {
                $vars['realip_header'] = $Config->web->server->realip->header;
            }
            $vars['realip_from'] = static::assocToValArray($Config->web->server->realip->from);
        }

        foreach ($Config->exportArrayFrom('web') as $k => $v) {
            if (substr($k, 0, 4) == 'web_') {
                $vars[$k] = $v;
            }
        }

        return $vars;
    }

    protected static function assocToValArray($array) {
        if (!Corelib\ArrayTools::isTraversable($array)) {
            $array = [$array];
        }

        $result = [];

        foreach ($array as $key => $value) {
            $result[] = ['key' => $key, 'value' => $value];
        }
        return $result;
    }

    protected function getConfigLocations($app_root, $AppEnv) {
        $config_roots = ["{$app_root}config/"];
        if ($AppEnv->has('cfg_path_extra')) {
            $config_roots = array_merge($config_roots, static::fixPathes($AppEnv->cfg_path_extra->exportArray()));
        }
        $result = [];
        foreach ($this->getConfigSubdirs($AppEnv) as $subdir) {
            foreach ($config_roots as $dir) {
                if (is_dir($dir . $subdir)) {
                    $result[] = $dir . $subdir;
                }
            }
        }
        return $result;
    }

    protected function getConfigSubdirs($AppEnv) {
        return $AppEnv->get('configuration_name') ? ['', $AppEnv->get('configuration_name') . '/'] : [''];
    }

    protected static function fixPathes(array $dirnames) {
        return array_map([self, 'fixPath'], $dirnames);
    }

    protected static function fixPath($dirname) {
        if (substr($dirname, 0, 1) != '/') {
            $dirname = realpath($dirname);
            if (!$dirname) {
                throw new \RuntimeException("Cannot resolve realpath of '$dirname'");
            }
        }
        return rtrim($dirname, '/') . '/';
    }

    protected function printHelp() {
        (new Help([
            '-o filename' => 'Output to <filename>, default: stdout',
            '-h' => 'Show help',
        ], 'buildngx arguments:'))->displayTo($this->Response);
    }

}
