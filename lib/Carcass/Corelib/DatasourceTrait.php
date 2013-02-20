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
