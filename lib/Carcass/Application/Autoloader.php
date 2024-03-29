<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

/**
 * Carcass autoloader implementation, PSR-0 compliant
 * @package Carcass\Application
 */
class Autoloader {

    protected $extension = '.php';

    protected $collector = [];

    /**
     * @param array $lib_pathes  search pathes
     * @param string $extension  override php extension, starting with '.', e.g. '.phtml'
     */
    public function __construct(array $lib_pathes = [], $extension = null) {
        $lib_pathes and $this->addToIncludePath($lib_pathes);
        $extension and $this->extension = $extension;
        spl_autoload_register([$this, 'resolve']);
    }

    /**
     * @param array $lib_pathes  additional search pathes
     * @return $this
     */
    public function addToIncludePath(array $lib_pathes) {
        set_include_path(join(':', array_unique(array_merge(explode(':', get_include_path()), $lib_pathes))));
        return $this;
    }

    /**
     * @param $class_name
     * @return bool
     */
    public function resolve($class_name) {
        $file_relative_path = ltrim(strtr($class_name, ['\\' => '/', '_' => '/']), '/') . $this->extension;
        if ($file_abs_path = stream_resolve_include_path($file_relative_path)) {
            require_once $this->collector[$class_name] = $file_abs_path;
            return true;
        }
        return false;
    }

    /**
     * Returns the list of autoloaded files
     * @return array of (class name => file pathname)
     */
    public function getAutoloadedFiles() {
        return $this->collector;
    }

}
