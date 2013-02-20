<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

/**
 * TransactionalConnectionInterface
 *
 * @package Carcass\Connection
 */
interface TransactionalConnectionInterface {

    /**
     * @param Manager $Manager
     * @return $this
     */
    public function setManager(Manager $Manager);

    /**
     * @param bool $local
     * @return $this
     */
    public function begin($local = false);

    /**
     * @param bool $local
     * @return $this
     */
    public function commit($local = false);

    /**
     * @param bool $local
     * @return $this
     */
    public function rollback($local = false);

    /**
     * @return string|null
     */
    public function getTransactionId();

}
