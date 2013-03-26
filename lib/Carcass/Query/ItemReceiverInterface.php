<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Query;


/**
 * Item receiver interface: supports the importItem method called by Query\*Dispatcher::sendTo
 *
 * @package Carcass\Query
 */
interface ItemReceiverInterface {

    /**
     * @param array|null $data
     * @return $this
     */
    public function importItem(array $data = null);

}