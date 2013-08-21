<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Tokumx;

use Carcass\Connection\ConnectionInterface;
use Carcass\Connection\Dsn;
use Carcass\Connection\TransactionalConnectionInterface;
use Carcass\Connection\TransactionalConnectionTrait;
use Carcass\Corelib;
use \MongoClient;
use \MongoDB;

/**
 * TokuMX Connection
 * @package Carcass\Tokumx
 */
class Connection implements ConnectionInterface, TransactionalConnectionInterface {
    use TransactionalConnectionTrait {
        begin as protected tcbegin;
    }

    const DSN_TYPE = 'mongodb';

    /**
     * @var \Carcass\Connection\Dsn
     */
    protected $Dsn;

    /**
     * @var \MongoClient
     */
    protected $MongoClient = null;

    /**
     * @var \MongoDB
     */
    protected $Db = null;

    /**
     * @param \Carcass\Connection\Dsn $Dsn
     */
    public function __construct(Dsn $Dsn) {
        Corelib\Assert::that('DSN has type ' . static::DSN_TYPE)->is(static::DSN_TYPE, $Dsn->getType());
        $this->Dsn = $Dsn;
    }

    /**
     * @param Dsn $Dsn
     * @return \Carcass\Connection\ConnectionInterface
     */
    public static function constructWithDsn(Dsn $Dsn) {
        return new static($Dsn);
    }

    /**
     * @return Dsn
     */
    public function getDsn() {
        return $this->Dsn;
    }

    /**
     * @return MongoDB
     */
    public function getDb() {
        if (null === $this->Db) {
            $this->selectDb();
        }
        return $this->Db;
    }

    /**
     * @param string|null $db_name
     * @throws \LogicException
     * @return MongoDB
     */
    public function selectDb($db_name = null) {
        if (!$db_name) {
            $db_name = $this->Dsn->get('name');
            if (!$db_name) {
                throw new \LogicException("No db_name argument passed, and no database name is defined in the dsn string");
            }
        }
        $this->Db = $this->getMongoClient()->$db_name;
        return $this->Db;
    }

    public function begin($local = false) {
        $this->tcbegin($local);
        $this->triggerScheduledTransaction();
        return $this;
    }

    protected function getMongoClient() {
        if (null === $this->MongoClient) {
            $this->MongoClient = $this->initMongoClient();
        }
        return $this->MongoClient;
    }

    protected function initMongoClient() {
        return new MongoClient((string)$this->Dsn);
    }

    protected function beginTransaction() {
        $this->ensureIsOkResult($this->getDb()->execute('return db.runCommand("beginTransaction");'), ['status' => 'transaction began']);
    }

    protected function rollbackTransaction() {
        $this->ensureIsOkResult($this->getDb()->execute('return db.runCommand("rollbackTransaction");'), ['status' => 'transaction rolled back']);
    }

    protected function commitTransaction() {
        $this->ensureIsOkResult($this->getDb()->execute('return db.runCommand("commitTransaction");'), ['status' => 'transaction committed']);
    }

    protected function ensureIsOkResult($result, array $expected_kwargs = []) {
        $error = null;
        if (!$this->isOkResult($result, $expected_kwargs, $error)) {
            throw new \MongoException($error);
        }
    }

    protected function isOkResult($result, array $expected_kwargs = [], &$error = null) {
        if (empty($result) || !is_array($result)) {
            $error = "got_empty_result";
            return false;
        }
        if (!isset($result['retval'])) {
            $error = 'missing_retval';
            return false;
        }
        $retval = $result['retval'];
        if (!isset($result['ok']) || $result['ok'] != 1) {
            $error = static::getErrorString($retval);
            return false;
        }
        if (!isset($retval['ok']) || $retval['ok'] != 1) {
            $error = static::getErrorString($retval);
            return false;
        }
        $expected_kwargs_errors = [];
        foreach ($expected_kwargs as $key => $value) {
            if (!isset($retval[$key])) {
                $expected_kwargs_errors[] = "missing_$key";
            } elseif ($retval[$key] != $value) {
                $expected_kwargs_errors[] = "unexpected_value_$key: expected [$value], got [{$retval[$key]}]";
            }
        }
        if ($expected_kwargs_errors) {
            $error = 'Error(s): ' . join('; ', $expected_kwargs_errors);
            return false;
        }
        return true;
    }

    protected static function getErrorString(array $result, $prefix = 'Error: ') {
        $result_tokens = [];
        foreach ($result as $key => $value) {
            $result_tokens[] = "[$key]='$value'";
        }
        return $prefix . join('; ', $result_tokens);
    }

}