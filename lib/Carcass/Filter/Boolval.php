<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * Boolval filter
 * @package Carcass\Filter
 */
class Boolval implements FilterInterface {

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        $value = (bool)$value;
    }

}
