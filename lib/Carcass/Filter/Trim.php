<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * Trim filter
 * @package Carcass\Filter
 */
class Trim implements FilterInterface {

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        $value = trim($value);
    }

}
