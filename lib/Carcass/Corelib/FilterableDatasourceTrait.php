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
 *
 * @package Carcass\Corelib
 */
trait FilterableDatasourceTrait {

    /**
     * @param array $allowed_fields
     * @return array
     */
    public function exportFilteredArray($allowed_fields) {
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
    public function exportFilteredHash($allowed_fields) {
        /** @var Hash $this */
        $Filtered = clone $this;
        foreach ($Filtered as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                unset($Filtered[$key]);
            }
        }
        return $Filtered;
    }
}
