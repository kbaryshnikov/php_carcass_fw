<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

/**
 * PoolConnectionInterface
 * @package Carcass\Connection
 */
interface PoolConnectionInterface extends ConnectionInterface {

    /**
     * @param DsnPool $DsnPool
     * @return PoolConnectionInterface
     */
    public static function constructWithPool(DsnPool $DsnPool);

}
