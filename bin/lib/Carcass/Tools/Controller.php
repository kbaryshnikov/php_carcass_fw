<?php

namespace Carcass\Tools;

use Carcass\Application;
use Carcass\Corelib;
use Carcass\Config;

class Controller extends Application\Controller {

    public function dispatch($action, Corelib\Hash $Args) {
        if ($Args->get('h')) {
            $this->printHelp($Args->get(0), $action);
            return 0;
        }
        return parent::dispatch($action, $Args);
    }

    protected function getHelp() {
        return [];
    }

    protected function printHelp($method, $action) {
        (new Help($this->getHelp() + [
            '-h' => 'Show help',
        ], $method . ($action === 'Default' ? '' : '.' . lcfirst($action)) . ' arguments:'))->displayTo($this->Response);
    }

    protected function getAppConfig(&$app_root = null) {
        $app_root = rtrim($app_root ?: getcwd(), '/') . '/';

        $AppEnv = new Corelib\Hash(include "{$app_root}env.php");

        $Config = new Config\Reader($this->getConfigLocations($app_root, $AppEnv));
        $Config->addConfigVar('APP_ROOT', $app_root);

        return $Config;
    }

    protected function getConfigLocations($app_root, Corelib\Hash $AppEnv) {
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

    protected function getConfigSubdirs(Corelib\Hash $AppEnv) {
        return $AppEnv->get('configuration_name') ? ['', $AppEnv->get('configuration_name') . '/'] : [''];
    }


    protected static function fixPathes(array $dirnames) {
        return array_map([get_called_class(), 'fixPath'], $dirnames);
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


}