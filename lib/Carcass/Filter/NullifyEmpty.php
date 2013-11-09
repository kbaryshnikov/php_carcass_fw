<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * NullifyEmpty filter: sets value to null if it is an empty string, empty array, or "empty" non-string value
 * @package Carcass\Filter
 */
class NullifyEmpty implements FilterInterface {

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        if (is_string($value)) {
            if (!strlen($value)) {
                $value = null;
            }
        } elseif (is_array($value)) {
            if (!count($value)) {
                $value = null;
            }
        } else {
            if (!strlen($value)) {
                $value = null;
            }
        }
    }

}
