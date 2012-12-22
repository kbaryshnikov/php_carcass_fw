<?php

namespace Carcass\Tools;

use Carcass\Application as Application;
use Carcass\Corelib as Corelib;
use Carcass\Config as Config;

class BuildngxScript extends Application\Controller {

    public function actionDefault($Args) {
        $app_root = rtrim($Args->get('app-root', getcwd()), '/') . '/';
        $AppEnv = new Corelib\Hash(include "{$app_root}env.php");
        $cfg = new Config\Reader($this->getConfigLocations($app_root, $AppEnv));
        $ngxcfg = "{$app_root}config/nginx.conf.php";
        if (!file_exists($ngxcfg)) {
            $this->Response->writeErrorLn("File not found: '$ngxcfg'");
            return 1;
        }
        include $ngxcfg;
        return 0;
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
        return $AppEnv->get('configuration_name') ? [$AppEnv->get('configuration_name') . '/', ''] : [''];
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

}
