<?php

namespace Carcass\Connection;

trait TransactionalConnectionTrait {

    protected static
        $TRANSACTION_NONE = 0,
        $TRANSACTION_SCHEDULED = 1,
        $TRANSACTION_STARTED = 2;

    protected
        $ConnectionManager = null,
        $transaction_status = 0,
        $transaction_counter = 0;

    // protected function beginTransaction();
    // protected function rollbackTransaction();
    // protected function commitTransaction();
    // query method must call triggerScheduledTransaction()

    public function setManager(Manager $Manager) {
        $this->ConnectionManager = $Manager;
        return $this;
    }

    public function begin($local = false) {
        switch ($this->transaction_status) {
            case self::$TRANSACTION_NONE:
                $this->transaction_status = self::$TRANSACTION_SCHEDULED;
                $this->transaction_counter = 1;
                break;
            case self::$TRANSACTION_SCHEDULED:
            case self::$TRANSACTION_STARTED:
                $this->transaction_counter++;
                break;
        }
        if (!$local && $this->ConnectionManager) {
            $this->ConnectionManager->begin($this);
        }
        return $this;
    }

    public function commit($local = false) {
        switch ($this->transaction_status) {
            case self::$TRANSACTION_NONE:
            case self::$TRANSACTION_SCHEDULED:
                $this->transaction_counter = 0;
                break;
            case self::$TRANSACTION_STARTED:
                if ($this->transaction_counter === 1) {
                    $this->commitTransaction();
                }
                $this->transaction_counter--;
                break;
        }
        if ($this->transaction_counter == 0) {
            $this->transaction_status = self::$TRANSACTION_NONE;
        }
        if (!$local && $this->ConnectionManager) {
            $this->ConnectionManager->commit($this);
        }
        return $this;
    }

    public function rollback($local = false) {
        switch ($this->transaction_status) {
            case self::$TRANSACTION_STARTED:
                $this->rollbackTransaction();
                // no break intentionally
            case self::$TRANSACTION_NONE:
            case self::$TRANSACTION_SCHEDULED:
                $this->transaction_counter = 0;
                break;
        }
        $this->transaction_status = self::$TRANSACTION_NONE;
        if (!$local && $this->ConnectionManager) {
            $this->ConnectionManager->rollback($this);
        }
        return $this;
    }

    public function doInTransaction(Callable $fn, array $args = [], Callable $finally_fn = null) {
        $e = null;
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

    protected function triggerScheduledTransaction() {
        if ($this->transaction_status === self::$TRANSACTION_SCHEDULED) {
            $this->beginTransaction();
            $this->transaction_status = self::$TRANSACTION_STARTED;
        }
        return $this;
    }

}
