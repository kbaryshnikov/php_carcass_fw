<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * Intval filter
 * @package Carcass\Filter
 */
class Intval implements FilterInterface {

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        if (!is_int($value) || !ctype_digit($value) || !(substr($value, 0, 1) == '-' && ctype_digit(substr($value, 1)))) {
            $value = intval($value);
        }
    }

}
