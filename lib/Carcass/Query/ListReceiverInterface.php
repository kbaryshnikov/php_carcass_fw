<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Query;


/**
 * List receiver interface: supports the importList method called by Query*::sendListTo
 *
 * @package Carcass\Query
 */
interface ListReceiverInterface {

    /**
     * @param int $count
     * @param array $data
     * @return $this
     */
    public function importList($count, array $data);

}