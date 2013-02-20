<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Data receiver trait implementation for an array object.
 *
 * User must implement:
 *
 * @method void set($key, $value)
 * @method void delete($key)
 *
 * @package Carcass\Corelib
 */
trait ArrayObjectDataReceiverTrait {

    /**
     * @param string|int $key
     * @param mixed $value
     */
    protected function setArrayObjectItemByKey($key, $value) {
        $this->set($key, $value);
    }

    /**
     * @param string|int $key
     */
    protected function unsetArrayObjectItemByKey($key) {
        $this->delete($key);
    }
}
