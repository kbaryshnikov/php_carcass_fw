<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * Strval filter
 * @package Carcass\Filter
 */
class Strval implements FilterInterface {

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        $value = (string)$value;
    }

}
