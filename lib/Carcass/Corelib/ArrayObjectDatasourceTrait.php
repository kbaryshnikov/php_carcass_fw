<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Datasource trait implementation for an array object.
 *
 * User must implement:
 *
 * @method bool has($key)
 * @method mixed getRef($key) (must return by reference)
 *
 * @package Carcass\Corelib
 */
trait ArrayObjectDatasourceTrait {

    /**
     * @param $key
     * @return mixed
     */
    protected function hasArrayObjectItemByKey($key) {
        return $this->has($key);
    }

    /**
     * @param $key
     * @return mixed
     * @throws \OutOfBoundsException
     */
    protected function &getArrayObjectItemByKey($key) {
        if ($this->has($key)) {
            return $this->getRef($key);
        }
        throw new \OutOfBoundsException("Key is undefined: '$key'");
    }

}
