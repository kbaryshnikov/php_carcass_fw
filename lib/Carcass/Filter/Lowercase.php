<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * Lowercase filter
 * @package Carcass\Filter
 */
class Lowercase implements FilterInterface {

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        if (null !== $value) {
            $value = mb_strtolower($value);
        }
    }

}
