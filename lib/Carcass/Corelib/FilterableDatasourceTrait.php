<?php
/**
 * Carcass Framework
 *
 * @author    Anton Terekhov <anton.a.terekhov@gmail.com>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Implementation of FilterableDatasourceInterface.
 *
 * User must implement:
 * @method array getDataArrayPtr() must return reference to internal values storage
 *
 * @package Carcass\Corelib
 */
trait FilterableDatasourceTrait {

    /**
     * @param array $allowed_fields
     * @return array
     */
    public function exportFilteredArray(array $allowed_fields) {
        /** @var Hash $this */
        $filtered = $this->exportArray();
        foreach ($filtered as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                unset($filtered[$key]);
            }
        }
        return $filtered;
    }

    /**
     * @param array $allowed_fields
     * @return Hash
     */
    public function exportFilteredHash(array $allowed_fields) {
        /** @var Hash $this */
        $Filtered = clone $this;
        $ptr = &$Filtered->getDataArrayPtr();
        foreach ($ptr as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                unset($ptr[$key]);
            }
        }
        return $Filtered;
    }
}
