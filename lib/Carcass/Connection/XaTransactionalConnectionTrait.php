<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

/**
 * XaTransactionalConnectionTrait: XaTransactionalConnectionInterface implementation.
 *
 * Users must implement protected bool doXaVote().
 *
 * @package Carcass\Connection
 */
trait XaTransactionalConnectionTrait {
    use TransactionalConnectionTrait;

    /** @var bool|null */
    protected $xa_vote_result = null;

    /**
     * For inner transactions, always agree.
     * For before-real-commit situations, do the first phase, and return false if cannot commit.
     *
     * @throws \LogicException
     * @return bool
     */
    public function vote() {
        switch ($this->transaction_status) {
            case self::$TRANSACTION_STATUS_NONE:
            case self::$TRANSACTION_STATUS_SCHEDULED:
                return true;
            case self::$TRANSACTION_STATUS_STARTED:
                if ($this->transaction_counter === 1) {
                    return $this->xa_vote_result = $this->doXaVote();
                }
                break;
        }
        return true;
    }

    /**
     * @param bool $local
     * @throws \LogicException
     * @throws \RuntimeException
     * @return $this
     */
    public function commit($local = false) {
        if (!$local && $this->ConnectionManager) {
            /** @noinspection PhpParamsInspection */
            $this->ConnectionManager->commit($this);
        }
        switch ($this->transaction_status) {
            case self::$TRANSACTION_STATUS_NONE:
            case self::$TRANSACTION_STATUS_SCHEDULED:
                $this->transaction_counter = 0;
                break;
            case self::$TRANSACTION_STATUS_STARTED:
                if ($this->transaction_counter === 1) {
                    if (null === $this->xa_vote_result) {
                        if ($this->ConnectionManager) {
                            throw new \LogicException(get_class($this) . ": Should always have vote performen when managed");
                        }
                        $this->xa_vote_result = $this->doXaVote();
                        if (!$this->xa_vote_result) {
                            throw new \RuntimeException(get_class($this) . ": XA Vote returned false");
                        }
                    }
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
     * @return bool
     * @throws \LogicException
     */
    protected function doXaVote() {
        throw new \LogicException(get_class($this) . ": Implementation required");
    }

}