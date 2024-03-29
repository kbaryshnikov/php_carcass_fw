<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * ImportableInterface
 * @package Carcass\Corelib
 */
interface ImportableInterface {

    /**
     * @param \Traversable|array $data
     * @param bool $no_overwrite
     * @return $this
     */
    public function import(/* Traversable */ $data, $no_overwrite = false);

}
