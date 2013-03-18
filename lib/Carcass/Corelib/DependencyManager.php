<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

use Carcass\Application;

class DependencyManager {

    /** @var bool|array filter, or bool true for all / false for none */
    protected $import_carcass_dependencies = false;

    protected $libraries = [];

    public function addLibrary($name, array $config) {
        $this->libraries[$name] = $config;
        return $this;
    }

    public function addLibraries(array $libraries) {
        $this->libraries = $libraries + $this->libraries;
        return $this;
    }

    /**
     * @param array|bool $filter  array of library names, or boolean: true for all / false for none
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function importCarcassDependencies($filter) {
        if (!is_bool($filter) && !is_array($filter)) {
            throw new \InvalidArgumentException('Boolean|Array argument is required');
        }
        $this->import_carcass_dependencies = $filter;
        return $this;
    }

    public function update($vendor_directory) {
        $libraries = $this->libraries;
        if ($this->import_carcass_dependencies) {
            $libraries += Application\Dependencies::get($this->import_carcass_dependencies);
        }
        if ($libraries) {
            $vendor_directory = rtrim($vendor_directory, '/') . '/';
            foreach ($libraries as $library_config) {
                $this->updateLibrary($library_config);
            }
        }
    }

    protected function updateLibrary(array $library_config) {

    }

}