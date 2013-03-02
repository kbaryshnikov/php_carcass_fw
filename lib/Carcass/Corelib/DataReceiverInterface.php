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
     * @param bool $no_overwrite
     * @return mixed
     */
    public function fetchFrom(\Traversable $Source, $no_overwrite = false);

    /**
     * @param array $source
     * @param bool $no_overwrite
     * @return mixed
     */
    public function fetchFromArray(array $source, $no_overwrite = false);

}
