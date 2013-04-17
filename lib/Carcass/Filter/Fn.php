<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * Filters with callback/closure
 * @package Carcass\Filter
 */
class Fn implements FilterInterface {

    /**
     * @var callable
     */
    protected $fn;

    /**
     * @param callable $fn must get value as argument and return filtered value
     */
    public function __construct(Callable $fn) {
        $this->fn = $fn;
    }

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        $fn = $this->fn;
        $value = $fn($value);
    }

}
