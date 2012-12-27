<?php

namespace Carcass\Config;

use Carcass\Corelib;

class Reader extends Item {

    protected $config_file_template = '%s.config.php';
    protected $search_dirs;

    public function __construct(array $search_dirs, $config_file_template = null) {
        $this->search_dirs = array_map(function($dir) { return rtrim($dir, '/') . '/'; }, $search_dirs);
        $config_file_template and $this->config_file_template = $config_file_template;
    }

    public function has($key) {
        if (parent::has($key)) {
            return true;
        }
        if (null !== $config_data = $this->fetchConfigData($key)) {
            $this->import([$key => $config_data]);
            return true;
        }
        return false;
    }

    protected function fetchConfigData($name) {
        if (!$config_files = $this->findConfigFilesForItem($name)) {
            return null;
        }
        $result = [];
        foreach (array_reverse($config_files) as $config_file) {
            Corelib\ArrayTools::mergeInto($result, $this->readConfigFile($config_file));
        }
        return $result;
    }

    protected function readConfigFile($config_file) {
        return (array)(include $config_file);
    }

    protected function configFileExists($config_file) {
        return file_exists($config_file);
    }

    protected function findConfigFilesForItem($name) {
        $config_files = [];
        foreach ($this->search_dirs as $dir) {
            $config_file_candidate = $dir . sprintf($this->config_file_template, $name);
            if ($this->configFileExists($config_file_candidate)) {
                $config_files[] = $config_file_candidate;
            }
        }
        return $config_files;
    }

}
