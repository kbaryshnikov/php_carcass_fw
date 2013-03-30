<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * Uppercase filter
 * @package Carcass\Filter
 */
class Uppercase implements FilterInterface {

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        if (null !== $value) {
            $value = mb_strtoupper($value);
        }
    }

}
