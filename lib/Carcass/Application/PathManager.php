<?php

namespace Carcass\Application;

class PathManager {

    protected static $default_paths = [
        'application'   => '',
        'pages'         => 'pages/',
        'scripts'       => 'scripts/',
        'var'           => 'var/',
    ];

    protected $app_root;
    protected $paths;

    public function __construct($app_root, array $paths = []) {
        $this->app_root = rtrim($app_root, '/') . '/';
        $this->setPaths($paths + static::$default_paths);
    }

    public function setPaths(array $paths) {
        foreach ($paths as $location_name => $path) {
            $this->setPath($location_name, $path);
        }
        return $this;
    }

    public function setPath($location_name, $path) {
        $path = rtrim($path, '/') . '/';
        if ($path === '/') {
            $path = $this->app_root;
        } elseif (substr($path, 0, 1) !== '/') {
            $path = $this->app_root . $path;
        }
        $this->paths[$location_name] = $path;
        return $this;
    }

    public function getAppRoot() {
        return $this->app_root;
    }

    public function getPathTo($location_name, $suffix = '') {
        if (!isset($this->paths[$location_name])) {
            throw new \RuntimeException("Location with name '$location_name' is not registered");
        }
        return $this->paths[$location_name] . ltrim($suffix, '/');
    }

    public function getPathToPhpFile($location_name, $filename, $extension = '.php') {
        return $this->getPathTo($location_name, strtr($filename, ['_' => '/', '\\' => '/']) . $extension);
    }

}
