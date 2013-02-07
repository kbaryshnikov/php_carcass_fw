<?php

namespace Carcass\Query;

use Carcass\Corelib;
use Carcass\Database;
use Carcass\Application\Injector;

class Base {

    protected
        $FetchFn = null,
        $Db = null,
        $DbConn = null,
        $last_result = [];

    public function fetchRow($sql_query_template) {
        return $this->setFetchWith(function($Db, array $args) use ($sql_query_template) {
            return $Db->getRow($sql_query_template, $args);
        });
    }

    public function fetchAll($sql_query_template, array $keys = []) {
        return $this->setFetchWith(function($Db, array $args) use ($sql_query_template, $keys) {
            return $Db->getAll($sql_query_template, $args, $keys);
        });
    }

    public function fetchCol($sql_query_template, $col = null, $valcol = null) {
        return $this->setFetchWith(function($Db, array $args) use ($sql_query_template, $col, $valcol) {
            return $Db->getCol($sql_query_template, $args, $col, $valcol);
        });
    }

    public function execute(array $args = []) {
        $this->last_result = $this->FetchFn->__invoke($this->getDatabase(), $args);
        if (false === $this->last_result) {
            throw new \LogicException('Databse result is === false, this should never happen');
        }
        return $this;
    }

    public function insert($sql_query_template, array $args = array()) {
        return $this->modify($sql_query_template, $args)
            ? $this->getDatabase()->getLastInsertId()
            : null;
    }

    public function modify($sql_query_template, array $args = array()) {
        return $this->getDatabase()->query($sql_query_template, $args);
    }

    public function getLastResult() {
        return $this->last_result;
    }

    public function doInTransaction(Callable $fn, array $args = [], Callable $finally_fn = null) {
        return Injector::getConnectionManager()->doInTransaction($fn, $args, $finally_fn);
    }

    public function sendTo(Corelib\DataReceiverInterface $Target) {
        $Target->fetchFromArray($this->getLastResult() ?: []);
        return $this;
    }

    protected function setFetchWith(Closure $fn) {
        $this->FetchFn = $fn;
        return $this;
    }

    protected function getDatabase() {
        if (null === $this->Db) {
            $this->Db = $this->assembleDatabase();
        }
        return $this->Db;
    }

    protected function assembleDatabase() {
        return Database\Factory::assemble($this->getDatabaseConnection());
    }

    protected function getDatabaseConnection() {
        if (null === $this->DbConn) {
            $this->DbConn = $this->assembleDatabaseConnection();
        }
        return $this->DbConn;
    }

    protected function assembleDatabaseConnection() {
        return Injector::getConnectionManager()->getConnection($this->getDatabaseDsn());
    }

    protected function getDatabaseDsn() {
        return Injector::getConfigReader()->getPath('application.connections.database');
    }

}
