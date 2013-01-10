<?php

namespace Carcass\Mysql;

use \Carcass\Connection\ConnectionInterface;
use \Carcass\Connection\TransactionalConnectionInterface;
use \Carcass\Connection\TransactionalConnectionTrait;
use \Carcass\Connection\Dsn;
use \Carcass\Corelib;

class Connection implements ConnectionInterface, TransactionalConnectionInterface {
    use TransactionalConnectionTrait;

    protected
        $Dsn,
        $QueryParser = null,
        $Connection = null,
        $last_result = null;

    public static function constructWithDsn(Dsn $Dsn) {
        return new static($Dsn);
    }

    public function getQueryParser($template) {
        return new QueryParser($this, $template);
    }

    public function __construct(Dsn $Dsn) {
        Corelib\Assert::onFailureThrow('mysql dsn is required')->is('mysql', $Dsn->getType());

        static $reporting_was_setup = false;
        if (!$reporting_was_setup) {
            \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $reporting_was_setup = true;
        }

        $this->Dsn = $Dsn;
    }

    public function executeQueryTemplate($query_template, array $args = []) {
        return $this->executeQuery($this->getQueryParser($query_template)->parse($args));
    }

    public function executeQuery($query) {
        $this->triggerScheduledTransaction();
        return $this->doExecuteQuery($query);
    }

    public function fetch(\mysqli_result $result = null) {
        return $this->getResult($result)->fetch_assoc();
    }

    public function freeResult(\mysqli_result $result = null) {
        if (null !== $result = $this->getResult($result)) {
            $result->free();
        }
        return $this;
    }

    public function getNumRows(\mysqli_result $result = null) {
        return $this->getResult($result)->num_rows;
    }

    public function getAffectedRows() {
        return $this->getConnection()->affected_rows;
    }

    public function getLastInsertId() {
        return $this->getConnection()->insert_id ?: null;
    }

    public function close() {
        $result = true;
        if ($this->Connection) {
            $result = $this->Connection->close();
            $this->Connection = null;
        }
        return $result;
    }

    public function getWarnings() {
        return $this->getConnection()->get_warnings();
    }

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

    protected function getConnection() {
        if (null === $this->Connection) {
            $this->Connection = $this->createConnectionByCurrentDsn();
        }
        return $this->Connection;
    }

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

    protected function doExecuteQuery($query) {
        $result = $this->getConnection()->query($query);
        if ($result === false) {
            throw new \RuntimeException(__CLASS__ . ' error #' . $this->getConnection()->errno . ': ' . $this->getConnection()->error);
        }
        return $this->last_result = $result;
    }

}

