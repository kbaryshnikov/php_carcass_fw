<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Implementation of DatasourceInterface.
 *
 * User must implement:
 * @method array getDataArrayPtr() must return reference to internal values storage
 *
 * @package Carcass\Corelib
 */
trait DatasourceTrait {

    /**
     * @param mixed $key
     * @return bool
     */
    public function has($key) {
        return array_key_exists($key, $this->getDataArrayPtr());
    }

    /**
     * @param mixed $key
     * @param mixed $default_value
     * @return mixed
     */
    public function get($key, $default_value = null) {
        self::prepareDatasourceKey($key);
        return $this->has($key) ? $this->getDataArrayPtr()[$key] : $default_value;
    }

    /**
     * @param string $path dot-separated
     * @param mixed $default_value
     * @return $this|mixed
     */
    public function getPath($path, $default_value = null) {
        if (false === $key = strtok($path, '.')) {
            return $default_value;
        }
        $subpath = strtok(null);
        try {
            $Item = $this->get($key);
            if ($subpath !== false) {
                if ($Item instanceof static) {
                    return $Item->getPath($subpath, $default_value);
                } elseif (is_array($Item)) {
                    return ArrayTools::getPath($Item, $subpath, $default_value);
                }
                return $default_value;
            }
            return $Item;
        } catch (\OutOfBoundsException $e) {
            return $default_value;
        }
    }

    /**
     * @param mixed $key
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function __get($key) {
        if (!$this->has($key)) {
            throw new \OutOfBoundsException("Missing value for '$key'");
        }
        return $this->get($key);
    }

    /**
     * @param mixed $key
     */
    protected static function prepareDatasourceKey(&$key) {
        $key = is_object($key) ? ObjectTools::toString($key) : $key;
    }

}
