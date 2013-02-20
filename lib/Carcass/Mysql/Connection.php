<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mysql;

use \Carcass\Connection\ConnectionInterface;
use \Carcass\Connection\TransactionalConnectionInterface;
use \Carcass\Connection\TransactionalConnectionTrait;
use \Carcass\Connection\Dsn;
use \Carcass\Corelib;

/**
 * MySQL Connection
 * @package Carcass\Mysql
 */
class Connection implements ConnectionInterface, TransactionalConnectionInterface {
    use TransactionalConnectionTrait;

    const DSN_TYPE = 'mysql';

    /**
     * @var \Carcass\Connection\Dsn
     */
    protected $Dsn;
    /**
     * @var QueryParser
     */
    protected $QueryParser = null;
    /**
     * @var \mysqli
     */
    protected $Connection = null;

    protected $last_result = null;

    /**
     * @param \Carcass\Connection\Dsn $Dsn
     * @return static
     */
    public static function constructWithDsn(Dsn $Dsn) {
        return new static($Dsn);
    }

    /**
     * @return \Carcass\Connection\Dsn
     */
    public function getDsn() {
        return $this->Dsn;
    }

    /**
     * @param \Carcass\Connection\Dsn $Dsn
     */
    public function __construct(Dsn $Dsn) {
        Corelib\Assert::that('DSN has type ' . static::DSN_TYPE)->is(static::DSN_TYPE, $Dsn->getType());

        static $reporting_was_setup = false;
        if (!$reporting_was_setup) {
            \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $reporting_was_setup = true;
        }

        $this->Dsn = $Dsn;
    }

    /**
     * @param $query
     * @return bool|\mysqli_result
     */
    public function executeQuery($query) {
        $this->triggerScheduledTransaction();
        return $this->doExecuteQuery($query);
    }

    /**
     * @param \mysqli_result $result
     * @return bool
     */
    public function fetch(\mysqli_result $result = null) {
        return $this->getResult($result)->fetch_assoc() ?: false;
    }

    /**
     * @param \mysqli_result $result
     * @return $this
     */
    public function freeResult(\mysqli_result $result = null) {
        if (null !== $result = $this->getResult($result)) {
            $result->free();
        }
        return $this;
    }

    /**
     * @param \mysqli_result $result
     * @return int
     */
    public function getNumRows(\mysqli_result $result = null) {
        return $this->getResult($result)->num_rows;
    }

    /**
     * @return int
     */
    public function getAffectedRows() {
        return $this->getConnection()->affected_rows;
    }

    /**
     * @return null
     */
    public function getLastInsertId() {
        return $this->getConnection()->insert_id ?: null;
    }

    /**
     * @return bool
     */
    public function close() {
        $result = true;
        if ($this->Connection) {
            $result = $this->Connection->close();
            $this->Connection = null;
        }
        return $result;
    }

    /**
     * @return \mysqli_warning
     */
    public function getWarnings() {
        return $this->getConnection()->get_warnings();
    }

    /**
     * @param $s
     * @return string
     */
    public function escapeString($s) {
        return $this->getConnection()->escape_string($s);
    }

    protected function beginTransaction() {
        $this->doExecuteQuery('BEGIN');
    }

    protected function rollbackTransaction() {
        $this->doExecuteQuery('ROLLBACK');
    }

    protected function commitTransaction() {
        $this->doexecuteQuery('COMMIT');
    }

    /**
     * @return \mysqli|null
     */
    protected function getConnection() {
        if (null === $this->Connection) {
            $this->Connection = $this->createConnectionByCurrentDsn();
        }
        return $this->Connection;
    }

    /**
     * @param \mysqli_result $result
     * @param bool $allow_empty
     * @return \mysqli_result|null
     * @throws \LogicException
     */
    protected function getResult(\mysqli_result $result = null, $allow_empty = false) {
        if (null === $result) {
            $result = $this->last_result;
        }
        if (null === $result) {
            if ($allow_empty) {
                return null;
            }
            throw new \LogicException('Nothing to fetch: no result argument passed, and last_result is null');
        }
        return $result;
    }

    /**
     * @return \mysqli
     */
    protected function createConnectionByCurrentDsn() {
        $Connection = new \mysqli(
            $this->Dsn->get('hostname', null),
            $this->Dsn->get('user', null),
            $this->Dsn->get('password', null),
            $this->Dsn->get('name', null),
            $this->Dsn->get('port', null),
            $this->Dsn->get('socket', null)
        );
        $Connection->set_charset($this->Dsn->args->get('charset', 'utf8'));
        return $Connection;
    }

    /**
     * @param $query
     * @return bool|\mysqli_result
     * @throws \RuntimeException
     */
    protected function doExecuteQuery($query) {
        $result = $this->getConnection()->query($query);
        if ($result === false) {
            throw new \RuntimeException(__CLASS__ . ' error #' . $this->getConnection()->errno . ': ' . $this->getConnection()->error);
        }
        return $this->last_result = $result;
    }

}

