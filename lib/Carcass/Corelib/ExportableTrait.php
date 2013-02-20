<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Implementation of ExportableInterface.
 *
 * User must implement:
 * @method array getDataArrayPtr() must return reference to internal values storage
 *
 * @package Carcass\Corelib
 */
trait ExportableTrait {

    /**
     * @return array
     */
    public function exportArray() {
        $result = [];
        foreach ($this->getDataArrayPtr() as $key => $value) {
            $result[$key] = $value instanceof ExportableInterface ? $value->exportArray() : $value;
        }
        return $result;
    }

}
