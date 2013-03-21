<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Less;

use Carcass\Application\WarningException;
use Carcass\Application\DI;
use Carcass\Fs;
use Carcass\Corelib;

class Cacher_File implements Cacher_Interface {

    public function __construct($cache_dir) {
        $this->cache_dir = rtrim($cache_dir, '/');
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        $cache_file = $this->getCacheFile($key);
        if (!file_exists($cache_file)) {
            return false;
        }
        try {
            $cache_data = file_get_contents($cache_file);
            var_dump($cache_file, ".");
            return Corelib\JsonTools::decode($cache_data);
        } catch (WarningException $e) {
            DI::getDebugger()->dumpException($e);
            DI::getLogger()->logException($e, 'Notice');
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function put($key, $value) {
        Fs\Directory::mkdirIfNotExists($this->cache_dir);
        $cache_data = Corelib\JsonTools::encode($value);
        $cache_file = $this->getCacheFile($key);
        var_dump($cache_file,  "!");
        file_put_contents($cache_file, $cache_data, LOCK_EX);
        return $this;
    }

    protected function getCacheFile($key) {
        return sprintf('%s/%s.less-cache', $this->cache_dir, md5($key));
    }
}