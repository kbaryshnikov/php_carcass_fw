<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

/**
 * Application pathes manager.
 * In usual workflow, application instance initializes it and registers the instance in the DI as 'PathManager'.
 * @package Carcass\Application
 */
class PathManager {

    protected static $default_paths = [
        'application' => '',
        'pages'       => 'pages/',
        'scripts'     => 'scripts/',
        'templates'   => 'templates/',
        'var'         => 'var/',
    ];

    protected $app_root;
    protected $paths;

    /**
     * @param string $app_root application root.
     * @param array $paths pathes map, array of 'location name' => 'path'. Pathes are relative to $app_root unless start with '/'.
     */
    public function __construct($app_root, array $paths = []) {
        $this->app_root = rtrim($app_root, '/') . '/';
        $this->setPaths($paths + static::$default_paths);
    }

    /**
     * @param array $paths pathes map, array of 'location name' => 'path'. Pathes are relative to $app_root unless start with '/'.
     * @return $this
     */
    public function setPaths(array $paths) {
        foreach ($paths as $location_name => $path) {
            $this->setPath($location_name, $path);
        }
        return $this;
    }

    /**
     * @param $location_name
     * @param $path
     * @return $this
     */
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

    /**
     * @return string
     */
    public function getAppRoot() {
        return $this->app_root;
    }

    /**
     * @param string $path if does not start with '/', gets prefixed with application root, otherwise returned as is
     * @return string absolute path
     */
    public function getAbsolutePath($path) {
        if (substr($path, 0, 1) !== '/') {
            return $this->app_root . $path;
        } else {
            return $path;
        }
    }

    /**
     * @param $location_name
     * @param string $suffix
     * @return string
     * @throws \RuntimeException
     */
    public function getPathTo($location_name, $suffix = '') {
        if (!isset($this->paths[$location_name])) {
            throw new \RuntimeException("Location with name '$location_name' is not registered");
        }
        return $this->paths[$location_name] . ltrim($suffix, '/');
    }

    /**
     * @param $location_name
     * @param $filename
     * @param string $extension
     * @return string
     */
    public function getPathToPhpFile($location_name, $filename, $extension = '.php') {
        return $this->getPathTo($location_name, strtr($filename, ['_' => '/', '\\' => '/']) . $extension);
    }

}
