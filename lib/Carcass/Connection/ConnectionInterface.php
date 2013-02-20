<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

/**
 * Basic conection interface
 *
 * @package Carcass\Connection
 */
interface ConnectionInterface {

    /**
     * @param Dsn $Dsn
     * @return ConnectionInterface
     */
    public static function constructWithDsn(Dsn $Dsn);

    /**
     * @return Dsn
     */
    public function getDsn();

}
