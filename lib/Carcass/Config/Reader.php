<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Config;

use Carcass\Corelib;

/**
 * Configuration reader: configuration item able to read itself
 * from configuration files.
 *
 * Recursively merges configuration files: global and per-env, with priority of per-env file.
 * Configured during the application instance bootstrap process.
 *
 * @package Carcass\Config
 */
class Reader extends Item {

    /**
     * @var string
     */
    protected $config_file_template = '%s.config.php';
    /**
     * @var array
     */
    protected $search_dirs;
    /**
     * @var array
     */
    protected $config_vars = [];

    /**
     * @param array $search_dirs
     * @param string|null $config_file_template
     */
    public function __construct(array $search_dirs, $config_file_template = null) {
        $this->search_dirs = array_map(function($dir) { return rtrim($dir, '/') . '/'; }, $search_dirs);
        $config_file_template and $this->config_file_template = $config_file_template;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addConfigVar($key, $value) {
        return $this->setConfigVars([$key => $value]);
    }

    /**
     * @param array $vars
     * @return $this
     */
    public function setConfigVars(array $vars) {
        $this->config_vars = $vars + $this->config_vars;
        return $this;
    }

    /**
     * @return $this
     */
    public function cleanConfigVars() {
        $this->config_vars = [];
        return $this;
    }

    /**
     * @param string $key
     * @return bool
     */
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

    /**
     * @param string $name
     * @return array|null
     */
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

    /**
     * @param string $config_file
     * @return array
     */
    protected function readConfigFile($config_file) {
        extract($this->config_vars);
        return (array)(include $config_file);
    }

    /**
     * @param string $config_file
     * @return bool
     */
    protected function configFileExists($config_file) {
        return file_exists($config_file);
    }

    /**
     * @param string $name
     * @return array
     */
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
