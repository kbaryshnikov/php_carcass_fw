<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Postgresql;

use Carcass\Connection\ConnectionInterface;
use Carcass\Connection\XaTransactionalConnectionInterface;
use Carcass\Connection\XaTransactionalConnectionTrait;
use Carcass\Connection\Dsn;
use Carcass\Corelib;
use Carcass\Application\DI;
use Carcass\DevTools;
use Carcass\Database;

/**
 * PostgreSQL Connection
 *
 * @package Carcass\Postgresql
 */
class Connection extends Database\Connection implements ConnectionInterface, XaTransactionalConnectionInterface {

    const DSN_TYPE = 'pgsql';

    protected $last_result = null;

    protected $xa_is_prepared = false;

    /**
     * @var resource
     */
    protected $conn_handle = null;

    /**
     * @param \Carcass\Connection\Dsn $Dsn
     */
    public function __construct(Dsn $Dsn) {
        Corelib\Assert::that('DSN has type ' . static::DSN_TYPE)->is(static::DSN_TYPE, $Dsn->getType());
        $this->Dsn = $Dsn;
    }

    /**
     * @return Client
     */
    public function assembleClient() {
        return new Client($this);
    }

    /**
     * @param resource|null $result
     * @return array|null
     */
    public function fetch($result = null) {
        $result = $this->getResult($result);
        $assoc = pg_fetch_assoc($result);
        return false === $assoc ? null : $assoc;
    }

    /**
     * @param resource|null $result
     * @return $this
     */
    public function freeResult($result = null) {
        if (null !== $result = $this->getResult($result, true)) {
            pg_free_result($result);
        }
        return $this;
    }

    /**
     * @param resource|null $result
     * @return int
     */
    public function getNumRows($result = null) {
        return (int)pg_num_rows($this->getResult($result));
    }

    /**
     * @param resource|null $result
     * @return int
     */
    public function getAffectedRows($result = null) {
        return (int)pg_affected_rows($this->getResult($result));
    }

    /**
     * @param string $s
     * @return string
     */
    public function escapeString($s) {
        return pg_escape_string($this->getConnectionHandle(), $s);
    }

    /**
     * @param $s
     * @return string
     */
    public function escapeByteA($s) {
        return pg_escape_bytea($this->getConnectionHandle(), $s);
    }

    /**
     * @param string|null $sequence
     * @throws \InvalidArgumentException
     * @return int|null
     */
    public function getLastInsertId($sequence = null) {
        if (null === $sequence) {
            $query = 'SELECT lastval()';
        } else {
            $query = sprintf("SELECT currval('%s')", $this->escapeString($sequence));
        }
        $res_h = pg_query($this->getConnectionHandle(), $query);
        if (false === $res_h) {
            return null;
        }
        $result_row = pg_fetch_array($res_h);
        if (empty($result_row)) {
            return null;
        }
        return reset($result_row);
    }

    /**
     * @return bool
     */
    public function close() {
        $result = true;
        if ($this->conn_handle) {
            $result = pg_close($this->conn_handle);
            $this->conn_handle = null;
        }
        return $result;
    }

    /**
     * @return resource
     */
    protected function createConnectionByCurrentDsn() {
        return $this->develCollectExecutionTime(
            function () {
                return 'connect: ' . $this->Dsn;
            },
            function () {
                $conn_tokens = [];
                if ($this->Dsn->has('socket')) {
                    $conn_tokens['host'] = $this->Dsn->socket;
                } elseif ($this->Dsn->has('hostname')) {
                    $conn_tokens['host'] = $this->Dsn->hostname;
                    $conn_tokens['port'] = $this->Dsn->get('port');
                }
                $conn_tokens['user'] = $this->Dsn->get('user');
                $conn_tokens['password'] = $this->Dsn->get('password');
                $conn_tokens['options'] = '--client_encoding=' . $this->Dsn->args->get('charset', 'UTF8');
                foreach ($conn_tokens as $key => $value) {
                    if (null === $value) {
                        unset($conn_tokens[$key]);
                    } else {
                        $conn_tokens[$key] = $key . '=' . "'" . str_replace("'", "''", $value) . "'";
                    }
                }
                $conn_str = join(' ', $conn_tokens);
                try {
                    $conn_h = pg_connect($conn_str, true);
                } catch (\Exception $e) {
                    throw new \RuntimeException("Could not connect: postgresql <$conn_str>", 0, $e);
                }
                return $conn_h;
            }
        );
    }

    /**
     * @return resource
     */
    protected function getConnectionHandle() {
        if (null === $this->conn_handle) {
            $this->conn_handle = $this->createConnectionByCurrentDsn();
        }
        return $this->conn_handle;
    }

    /**
     * @param $query
     * @throws \RuntimeException
     * @throws \Exception
     * @return resource
     */
    protected function doExecuteQuery($query) {
        $h = $this->getConnectionHandle();

        $result = $this->develCollectExecutionTime(
            $query, function () use ($query, $h) {
                return pg_query($h, $query);
            }
        );

        if ($result === false) {
            throw new \RuntimeException(__CLASS__ . ' error ' . pg_last_error($h));
        }

        return $this->last_result = $result;
    }

    /**
     * @param resource $result
     * @param bool $allow_empty
     * @throws \LogicException
     * @return resource
     */
    protected function getResult($result = null, $allow_empty = false) {
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

    protected function doExecuteXaQuery($xa_query) {
        $this->doExecuteQuery(
            str_replace(
                '#',
                "'" . $this->escapeString($this->getXaId()) . "'",
                $xa_query
            )
        );
    }

    protected function getXaId() {
        return $this->getConnectionId() . '_' . $this->getTransactionId();
    }

    protected function prepareXa() {
        $this->doExecuteXaQuery('PREPARE TRANSACTION #');
        $this->xa_is_prepared = true;
    }

    protected function ensureXaIsPrepared() {
        if (!$this->xa_is_prepared) {
            $this->prepareXa();
        }
    }

    protected function beginTransaction() {
        $this->doExecuteQuery('BEGIN');
        $this->xa_is_prepared = false;
    }

    protected function rollbackTransaction() {
        if (!$this->xa_is_prepared) {
            $this->executeQuery('ROLLBACK');
        } else {
            $this->doExecuteXaQuery('ROLLBACK PREPARED #');
            $this->xa_is_prepared = false;
        }
    }

    protected function commitTransaction() {
        $this->ensureXaIsPrepared();
        $this->doexecuteXaQuery('COMMIT PREPARED #');
        $this->xa_is_prepared = false;
    }

    protected function doXaVote() {
        try {
            $this->prepareXa();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

}