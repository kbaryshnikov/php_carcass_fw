<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

/**
 * XaTransactionInterface: interface for transactions supporting two phase commits.
 * Adds the vote method.
 *
 * @package Carcass\Connection
 */
interface XaTransactionalConnectionInterface extends TransactionalConnectionInterface {

    /**
     * @param bool $local
     * @return bool
     */
    public function vote($local = false);

}
