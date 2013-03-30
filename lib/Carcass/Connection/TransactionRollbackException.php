<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

/**
 * If ConnectionManager catches this exception thrown by doInTranscation() callback,
 * it rollbacks but returns $result instead of re-throwing.
 *
 * @package Carcass\Connection
 */
class TransactionRollbackException extends \RuntimeException {

    protected $result;

    /**
     * @param mixed $result
     */
    public function __construct($result) {
        $this->result = $result;
        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function getResult() {
        return $this->result;
    }

}
