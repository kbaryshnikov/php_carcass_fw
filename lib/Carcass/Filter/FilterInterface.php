<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * FilterInterface
 * @package Carcass\Filter
 */
interface FilterInterface {

    /**
     * @param mixed $value value to filter, by reference
     * @return void
     */
    public function filter(&$value);

}
