<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

/**
 * DsnInterface
 * @package Carcass\Connection
 */
interface DsnInterface {

    /**
     * @return string|null
     */
    public function getType();

    /**
     * @return string
     */
    public function __toString();

}