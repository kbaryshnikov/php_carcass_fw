<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * DomainNormalize filter - lowercases the domain and trim '.'
 * @package Carcass\Filter
 */
class DomainNormalize implements FilterInterface {

    /**
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function filter(&$value) {
        if (null === $value) {
            return;
        }
        $value = strtolower(trim($value, '.'));
    }

}
