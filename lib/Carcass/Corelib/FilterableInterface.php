<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Class FilterableInterface
 * @package Carcass\Corelib
 */
interface FilterableInterface {

    /**
     * @param callable $fn
     * @return $this
     */
    public function filter(callable $fn);

}