<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

use Carcass\Corelib\UniqueId;

/**
 * TransactionalConnectionInterface implementation, with support
 * for pseudo-inner transactions via transaction counter.
 *
 * A user must implement protected methods: beginTransaction(), commitTransaction(), rollbackTransaction().
 *
 * Before running a real query on a connection, a user must call triggerScheduledTransaction().
 *
 * @package Carcass\Connection
 */
trait TransactionalConnectionTrait {

    // pseudo-constants (traits cannot define constants)
    protected static
        $TRANSACTION_STATUS_NONE = 0,
        $TRANSACTION_STATUS_SCHEDULED = 1,
        $TRANSACTION_STATUS_STARTED = 2;

    /**
     * @var Manager|null
     */
    protected $ConnectionManager = null;
    /**
     * @var string|null
     */
    protected $transaction_id = null;
    /**
     * @var int
     */
    protected $transaction_status = 0;
    /**
     * @var int
     */
    protected $transaction_counter = 0;

    /**
     * @param Manager $Manager
     * @return $this
     */
    public function setManager(Manager $Manager) {
        $this->ConnectionManager = $Manager;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getTransactionId() {
        if ($this->transaction_status == self::$TRANSACTION_STATUS_NONE) {
            return null;
        }
        if (null === $this->transaction_id) {
            if ($this->ConnectionManager) {
                $this->transaction_id = $this->ConnectionManager->getTransactionId('T');
            }
            if (null === $this->transaction_id) {
                $this->transaction_id = UniqueId::generate();
                if ($this->ConnectionManager) {
                    $this->ConnectionManager->setTransactionId($this->transaction_id);
                }
            }
        }
        return $this->transaction_id;
    }

    /**
     * @param bool $local
     * @return $this
     */
    public function begin($local = false) {
        switch ($this->transaction_status) {
            case self::$TRANSACTION_STATUS_NONE:
                $this->transaction_id = null;
                $this->transaction_status = self::$TRANSACTION_STATUS_SCHEDULED;
                $this->transaction_counter = 1;
                break;
            case self::$TRANSACTION_STATUS_SCHEDULED:
            case self::$TRANSACTION_STATUS_STARTED:
                $this->transaction_counter++;
                break;
        }
        if (!$local && $this->ConnectionManager) {
            $this->ConnectionManager->begin($this);
        }
        return $this;
    }

    /**
     * @param bool $local
     * @return $this
     */
    public function commit($local = false) {
        if (!$local && $this->ConnectionManager) {
            $this->ConnectionManager->commit($this);
        }
        switch ($this->transaction_status) {
            case self::$TRANSACTION_STATUS_NONE:
            case self::$TRANSACTION_STATUS_SCHEDULED:
                $this->transaction_counter = 0;
                break;
            case self::$TRANSACTION_STATUS_STARTED:
                if ($this->transaction_counter === 1) {
                    $this->commitTransaction();
                }
                $this->transaction_counter--;
                break;
        }
        if ($this->transaction_counter == 0) {
            $this->transaction_status = self::$TRANSACTION_STATUS_NONE;
        }
        return $this;
    }

    /**
     * @param bool $local
     * @return $this
     */
    public function rollback($local = false) {
        switch ($this->transaction_status) {
            case self::$TRANSACTION_STATUS_STARTED:
                $this->rollbackTransaction();
                // no break intentionally
            case self::$TRANSACTION_STATUS_NONE:
            case self::$TRANSACTION_STATUS_SCHEDULED:
                $this->transaction_counter = 0;
                break;
        }
        $this->transaction_status = self::$TRANSACTION_STATUS_NONE;
        if (!$local && $this->ConnectionManager) {
            $this->ConnectionManager->rollback($this);
        }
        return $this;
    }

    /**
     * Calls $fn inside a transaction.
     *
     * @param callable $fn               callback to run inside a started transaction
     * @param array $args                $fn callback arguments
     * @param callable|null $finally_fn  "finally" callback
     * @return mixed the $fn result
     * @throws \Exception                rollbacks if $fn throws a transaction, and throws it again after the "finally" code
     */
    public function doInTransaction(Callable $fn, array $args = [], Callable $finally_fn = null) {
        $e = null;
        $result = null;
        try {
            $this->begin();
            $result = call_user_func_array($fn, $args);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
        }
        if (null !== $finally_fn) {
            $finally_fn();
        }
        if (null !== $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * @return $this
     */
    protected function triggerScheduledTransaction() {
        if ($this->transaction_status === self::$TRANSACTION_STATUS_SCHEDULED) {
            $this->beginTransaction();
            $this->transaction_status = self::$TRANSACTION_STATUS_STARTED;
        }
        return $this;
    }

    protected function beginTransaction() {
        throw new \LogicException("Implementation required");
    }

    protected function rollbackTransaction() {
        throw new \LogicException("Implementation required");
    }

    protected function commitTransaction() {
        throw new \LogicException("Implementation required");
    }

}
