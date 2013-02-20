<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * DataReceiverInterface
 *
 * @package Carcass\Corelib
 */
interface DataReceiverInterface {

    /**
     * @param \Traversable $Source
     * @return mixed
     */
    public function fetchFrom(\Traversable $Source);

    /**
     * @param array $source
     * @return mixed
     */
    public function fetchFromArray(array $source);

}
