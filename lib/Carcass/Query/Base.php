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

    public function fetchWith(Callable $fn, Callable $finally_fn = null) {
        return $this->setFetchWith(function() use ($fn, $finally_fn) {
            return Injector::getConnectionManager()->doInTransaction($fn, func_get_args(), $finally_fn);
        });
    }

    public function execute(array $args = []) {
        $this->last_result = $this->doFetch($args);
        return $this;
    }

    protected function doFetch(array $args) {
        $result = call_user_func_array($this->FetchFn, $this->getCallbackArgs($args));
        if (false === $result) {
            throw new \LogicException('Database result is === false, this should never happen');
        }
        return $result;
    }

    public function insert($sql_query_template, array $args = array()) {
        return $this->modify($sql_query_template, $args)
            ? $this->getDatabase()->getLastInsertId()
            : null;
    }

    public function modify($sql_query_template, array $args = array()) {
        return $this->doModify(function($Db, $args) use ($sql_query_template) {
            return $Db->query($sql_query_template, $args);
        }, $args, false);
    }

    public function insertWith(Callable $fn, array $args, $in_transaction = true, Callable $finally_fn = null) {
        return $this->modifyWith($fn, $args, $in_transaction, $finally_fn);
    }

    public function modifyWith(Callable $fn, array $args, $in_transaction = true, Callable $finally_fn = null) {
        return $this->doModify($fn, $args, $in_transaction, $finally_fn);
    }

    protected function doModify(Callable $fn, array $args, $in_transaction, Callable $finally_fn = null) {
        if ($in_transaction) {
            $result = $this->doInTransaction($fn, $args, $finally_fn);
        } else {
            $result = call_user_func_array($fn, $this->getCallbackArgs($args));
        }
        return $result;
    }

    public function getLastResult() {
        return $this->last_result;
    }

    public function doInTransaction(Callable $fn, array $args = [], Callable $finally_fn = null) {
        return Injector::getConnectionManager()->doInTransaction($fn, $this->getCallbackArgs($args), $finally_fn);
    }

    public function sendTo(Corelib\ImportableInterface $Target) {
        $Target->import($this->getLastResult() ?: []);
        return $this;
    }

    public function getDatabase() {
        if (null === $this->Db) {
            $this->Db = $this->assembleDatabase();
        }
        return $this->Db;
    }

    protected function getCallbackArgs(array $args) {
        return [
            $this->getDatabase(),
            $args,
        ];
    }

    protected function setFetchWith(Callable $fn) {
        $this->FetchFn = $fn;
        return $this;
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
