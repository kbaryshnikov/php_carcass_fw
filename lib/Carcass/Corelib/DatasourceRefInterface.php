<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * DatasourceRefInterface: a DatasourceInterface that is able to return values by reference
 *
 * @package Carcass\Corelib
 */
interface DatasourceRefInterface extends DatasourceInterface {

    /**
     * @param mixed $key
     * @return mixed
     */
    public function &getRef($key);

}

