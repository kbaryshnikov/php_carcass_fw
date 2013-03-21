<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Query;

use Carcass\Corelib;
use Carcass\Application\DI;
use Carcass\Mysql;

/**
 * Base Query
 * @package Carcass\Query
 */
class Base {

    protected $db_dsn = null;
    protected $last_insert_id = null;
    protected $last_result = [];

    /**
     * @var callable|null
     */
    protected $before_call = null;
    /**
     * @var callable|null
     */
    protected $after_call = null;

    /**
     * @var callable|null
     */
    protected $result_converter_fn = null;

    /**
     * @var Mysql\Client
     */
    protected $Db = null;
    /**
     * @var callable|null
     */
    protected $FetchFn = null;

    /**
     * @param string $sql_query_template
     * @return $this
     */
    public function fetchRow($sql_query_template) {
        return $this->setFetchWith(
            function (Mysql\Client $Db, array $args) use ($sql_query_template) {
                return $Db->getRow($sql_query_template, $this->getArgs($args));
            }
        );
    }

    /**
     * @param string $sql_query_template
     * @param array $keys
     * @return $this
     */
    public function fetchAll($sql_query_template, array $keys = []) {
        return $this->setFetchWith(
            function (Mysql\Client $Db, array $args) use ($sql_query_template, $keys) {
                return $Db->getAll($sql_query_template, $this->getArgs($args), $keys);
            }
        );
    }

    /**
     * @param string $sql_query_template
     * @param null $col
     * @param null $valcol
     * @return $this
     */
    public function fetchCol($sql_query_template, $col = null, $valcol = null) {
        return $this->setFetchWith(
            function (Mysql\Client $Db, array $args) use ($sql_query_template, $col, $valcol) {
                return $Db->getCol($sql_query_template, $this->getArgs($args), $col, $valcol);
            }
        );
    }

    /**
     * Sets the result converter function: mixed $fn(mixed query result)
     * @param callable $fn
     * @return $this
     */
    public function setResultsConverter(Callable $fn = null) {
        $this->result_converter_fn = $fn;
        return $this;
    }

    /**
     * @param callable $fn
     * @param callable $finally_fn
     * @return $this
     */
    public function fetchWith(Callable $fn, Callable $finally_fn = null) {
        return $this->setFetchWith(
            function () use ($fn, $finally_fn) {
                return DI::getConnectionManager()->doInTransaction($fn, func_get_args(), $finally_fn);
            }
        );
    }

    /**
     * @param array $args
     * @return $this
     */
    public function execute(array $args = []) {
        $this->last_result = $this->doFetch($this->getArgs($args));
        return $this;
    }

    /**
     * @param array $args
     * @return array
     */
    protected function getArgs(array $args) {
        return $args;
    }

    /**
     * @param array $args
     * @return mixed
     * @throws \LogicException
     */
    protected function doFetch(array $args) {
        $result = call_user_func_array($this->FetchFn, $this->getCallbackArgs($args));
        if (false === $result) {
            throw new \LogicException('Database result is === false, this should never happen');
        }
        $result = $this->convertResult($result);
        return $result;
    }

    /**
     * @param callable $fn
     * @return $this
     */
    public function before(Callable $fn) {
        $this->before_call = $fn;
        return $this;
    }

    /**
     * @param callable $fn
     * @return $this
     */
    public function after(Callable $fn) {
        $this->after_call = $fn;
        return $this;
    }

    /**
     * @param string $sql_query_template
     * @param array $args
     * @return mixed
     */
    public function insert($sql_query_template, array $args = array()) {
        $this->doModify(
            function (Mysql\Client $Db, $args) use ($sql_query_template) {
                $affected_rows        = $Db->query($sql_query_template, $this->getArgs($args));
                $this->last_insert_id = $affected_rows ? $Db->getLastInsertId() : null;
                return $affected_rows;
            }, $args, false
        );
        return $this->last_insert_id;
    }

    /**
     * @param string $sql_query_template
     * @param array $args
     * @return mixed
     */
    public function modify($sql_query_template, array $args = array()) {
        return $this->doModify(
            function (Mysql\Client $Db, $args) use ($sql_query_template) {
                return $Db->query($sql_query_template, $this->getArgs($args));
            }, $args, false
        );
    }

    /**
     * @param callable $fn
     * @param array $args
     * @param bool $in_transaction
     * @param callable $finally_fn
     * @return mixed
     */
    public function insertWith(Callable $fn, array $args, $in_transaction = true, Callable $finally_fn = null) {
        return $this->modifyWith($fn, $args, $in_transaction, $finally_fn);
    }

    /**
     * @param callable $fn
     * @param array $args
     * @param bool $in_transaction
     * @param callable $finally_fn
     * @return mixed
     */
    public function modifyWith(Callable $fn, array $args, $in_transaction = true, Callable $finally_fn = null) {
        return $this->doModify($fn, $args, $in_transaction, $finally_fn);
    }

    /**
     * @param callable $fn
     * @param array $args
     * @param $in_transaction
     * @param callable $finally_fn
     * @return mixed
     */
    protected function doModify(Callable $fn, array $args, $in_transaction, Callable $finally_fn = null) {
        if (!$in_transaction && ($this->before_call !== null || $this->after_call !== null)) {
            $in_transaction = true;
        }
        if ($in_transaction) {
            $result = $this->doInTransaction($fn, $this->getArgs($args), $finally_fn);
        } else {
            $result = call_user_func_array($fn, $this->getCallbackArgs($this->getArgs($args)));
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getLastResult() {
        return $this->last_result;
    }

    /**
     * @param callable $fn
     * @param array $args
     * @param callable $finally_fn
     * @return mixed
     */
    public function doInTransaction(Callable $fn, array $args = [], Callable $finally_fn = null) {
        if (null !== $this->before_call) {
            $before_fn         = $this->before_call;
            $this->before_call = null;
        } else {
            $before_fn = null;
        }
        if (null !== $this->after_call) {
            $after_fn         = $this->after_call;
            $this->after_call = null;
        } else {
            $after_fn = null;
        }
        $trans_fn = function () use ($fn, $before_fn, $after_fn) {
            $args = func_get_args();
            if (null !== $before_fn) {
                call_user_func_array($before_fn, array_merge([$this], $args));
            }
            $result = call_user_func_array($fn, $args);
            if (null !== $after_fn) {
                call_user_func_array($after_fn, array_merge([$this], $args));
            }
            return $result;
        };
        return DI::getConnectionManager()->doInTransaction($trans_fn, $this->getCallbackArgs($args), $finally_fn);
    }

    /**
     * @param \Carcass\Corelib\ImportableInterface $Target
     * @return $this
     */
    public function sendTo(Corelib\ImportableInterface $Target) {
        $Target->import($this->getLastResult() ? : []);
        return $this;
    }

    /**
     * @return \Carcass\Mysql\Client|null
     */
    public function getDatabase() {
        if (null === $this->Db) {
            $this->Db = $this->assembleDatabaseClient();
        }
        return $this->Db;
    }

    /**
     * @param $result
     * @return array|mixed|null
     */
    protected function convertResult($result) {
        if (null !== $result && $this->result_converter_fn) {
            $fn = $this->result_converter_fn;
            return $fn($result);
        }
        return $result;
    }

    /**
     * @return \Carcass\Mysql\Client
     */
    protected function assembleDatabaseClient() {
        /** @var Mysql\Connection $Connection */
        $Connection = DI::getConnectionManager()->getConnection(
            DI::getConfigReader()->getPath('application.connections.database')
        );
        return new Mysql\Client($Connection);
    }

    /**
     * @param array $args
     * @return array
     */
    protected function getCallbackArgs(array $args) {
        return [
            $this->getDatabase(),
            $args,
        ];
    }

    /**
     * @param callable $fn
     * @return $this
     */
    protected function setFetchWith(Callable $fn) {
        $this->FetchFn = $fn;
        return $this;
    }

}
