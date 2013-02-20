<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Implementation of DatasourceRefInterface
 *
 * User must implement:
 * @method array getDataArrayPtr() must return reference to internal values storage
 *
 * @package Carcass\Corelib
 */
trait DatasourceRefTrait {
    use DatasourceTrait;

    /**
     * @param mixed $key
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function &getRef($key) {
        self::prepareDatasourceKey($key);
        if (!$this->has($key)) {
            throw new \OutOfBoundsException("Missing value for '$key'");
        }
        return $this->getDataArrayPtr()[$key];
    }

}

