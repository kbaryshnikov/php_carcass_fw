<?php

namespace Carcass\Mysql;

use \Carcass\Connection\ConnectionInterface;
use \Carcass\Connection\Dsn;
use \Carcass\Corelib;

// read only implementation
class HandlerSocket_Connection implements ConnectionInterface {
    
    const 
        DEFAULT_PORT = 9998,
        CONN_TIMEOUT = 1,
        SOCK_TIMEOUT = 1,
        SOCK_TIMEOUT_MS = 0;

    protected
        $Dsn,
        $exception_on_errors = true,
        $next_dbname = null,
        $socket = null,
        $indexes = array(),
        $index_sequence = 1,
        $last_error = null;

    public static function constructWithDsn(Dsn $Dsn) {
        return new static($Dsn);
    }

    public function __construct(Dsn $Dsn) {
        Corelib\Assert::onFailureThrow('handlersocket dsn is required')->is('handlersocket', $Dsn->getType());
        $this->Dsn = $Dsn;
    }

    public function throwExceptionOnErrors($bool) {
        $this->exception_on_errors = (bool)$bool;
        return $this;
    }

    public function disconnect() {
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->next_dbname = null;
            $this->indexes = array();
            $this->socket = null;
        }
    }

    public function __destruct() {
        $this->disconnect();
    }

    public function getIndex($id) {
        return isset($this->indexes[$id]) ? $this->indexes[$id] : null;
    }

    public function useDb($dbname) {
        $this->next_dbname = (string)$dbname;
        return $this;
    }

    public function openIndex($tablename, $indexname, array $cols, array $fcols = null, $index_id = null) {
        if (null === $index_id) {
            $index_id = $this->index_sequence++;
        }
        $dbname = null;
        if (null !== $this->next_dbname) {
            $dbname = $this->next_dbname;
            $this->next_dbname = null;
        }
        if (null === $dbname) {
            $dbname = $this->Dsn->get('name');
        }
        if (null === $dbname) {
            throw new \LogicException('Database not selected');
        }
        $args = array('P', $index_id, $dbname, $tablename, $indexname, join(',', $cols));
        if ($fcols) {
            $args[] = $fcols;
        }
        return new HandlerSocket_Index($this, $index_id, $cols, $args);
    }

    public function query(array $tokens, $Index = null) {
        if ($Index) {
            $this->ensureIndexIsOpened($Index);
        }
        $query = join("\t", $tokens);
        fwrite($this->h(), $query . "\n");
        $result = explode("\t", rtrim($raw_response = fgets($this->h())));
        if (!is_array($result) || !isset($result[0])) {
            throw new \LogicException("Malformed HandlerSocket response: " . $raw_response);
        }
        if (empty($result[0])) {
            $this->last_error = null;
            return array_slice($result, 1);
        } else {
            $this->last_error = $result;
            if ($this->exception_on_errors) {
                throw new \RuntimeException("Query [" . join(' ', $tokens) . "] failed: [" 
                    . join(" ", $this->last_error) . "]");
            }
            return false;
        }
    }

    public function getLastError() {
        return $this->last_error;
    }

    protected function ensureIndexIsOpened($Index) {
        if (!isset($this->indexes[$Index->getIndexId()])) {
            $Index->connect();
            $this->indexes[$Index->getIndexId()] = $Index;
        }
    }

    protected function h() {
        if (null === $this->socket) {
            if ($this->Dsn->has('socket')) {
                $this->socket = fsockopen('unix://' . $this->Dsn->socket, null, $errno, $errstr);
            } else {
                $this->socket = fsockopen(
                    $this->Dsn->get('hostname', 'localhost'),
                    $this->Dsn->get('port', static::DEFAULT_PORT),
                    $errno,
                    $errstr,
                    static::CONN_TIMEOUT
                );
            }
            stream_set_timeout($this->socket, static::SOCK_TIMEOUT, static::SOCK_TIMEOUT_MS);
        }
        if (null === $this->socket) {
            throw new \RuntimeException("Connection to {$this->Dsn} failed");
        }
        return $this->socket;
    }

}
