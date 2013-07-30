<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mysql;

use \Carcass\Connection\ConnectionInterface;
use \Carcass\Connection\Dsn;
use \Carcass\Corelib;
use \Carcass\DevTools;

/**
 * MySQL HandlerSocket Connection.
 * Read only implementation.
 *
 * @package Carcass\Mysql
 */
class HandlerSocket_Connection implements ConnectionInterface {
    use DevTools\TimerTrait;
    use Corelib\UniqueObjectIdTrait {
        Corelib\UniqueObjectIdTrait::getUniqueObjectId as getConnectionId;
    }

    const
        DEFAULT_PORT = 9998,
        CONN_TIMEOUT = 1,
        SOCK_TIMEOUT = 1,
        SOCK_TIMEOUT_MS = 0;

    /**
     * @var \Carcass\Connection\Dsn
     */
    protected $Dsn;

    protected
        $exception_on_errors = true,
        $next_dbname = null,
        $socket = null,
        $indexes = [],
        $index_sequence = 1,
        $last_error = null;

    /**
     * @param \Carcass\Connection\Dsn $Dsn
     * @return static
     */
    public static function constructWithDsn(Dsn $Dsn) {
        return new static($Dsn);
    }

    /**
     * @param \Carcass\Connection\Dsn $Dsn
     */
    public function __construct(Dsn $Dsn) {
        Corelib\Assert::that('DSN has hs type')->is('hs', $Dsn->getType());
        $this->Dsn = $Dsn;
    }

    /**
     * @return \Carcass\Connection\Dsn
     */
    public function getDsn() {
        return $this->Dsn;
    }

    /**
     * @param bool $enable
     * @return $this
     */
    public function throwExceptionOnErrors($enable) {
        $this->exception_on_errors = (bool)$enable;
        return $this;
    }

    public function disconnect() {
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->next_dbname = null;
            $this->indexes = [];
            $this->socket = null;
        }
    }

    public function __destruct() {
        $this->disconnect();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getIndex($id) {
        return isset($this->indexes[$id]) ? $this->indexes[$id] : null;
    }

    /**
     * @param string $dbname
     * @return $this
     */
    public function useDb($dbname) {
        $this->next_dbname = (string)$dbname;
        return $this;
    }

    /**
     * @param string $tablename
     * @param array $index ['PRIMARY' for PK, or index name => array of [ field => default ]]
     * @param array $cols
     * @param array $fcols
     * @param int|string|null $index_id
     * @return HandlerSocket_Index
     * @throws \LogicException
     */
    public function openIndex($tablename, array $index, array $cols, array $fcols = null, $index_id = null) {
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
        return new HandlerSocket_Index($this, $dbname, $tablename, $index, $index_id, $cols, $fcols);
    }

    /**
     * @param array $tokens
     * @param HandlerSocket_Index $Index
     * @return array|bool
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function query(array $tokens, $Index = null) {
        if ($Index) {
            $this->ensureIndexIsOpened($Index);
        }
        $query = join("\t", $tokens);
        $h = $this->h();
        $raw_response = null;
        $result = $this->develCollectExecutionTime(
            $query,
            function () use ($h, $query, &$raw_response) {
                fwrite($h, $query . "\n");
                $result = array_map(
                    function ($value) {
                        if ($value === "\x00") {
                            return null;
                        }
                        return preg_replace_callback(
                            "/\x01(.)/",
                            function ($match) {
                                return chr(ord($match[1]) - 0x40);
                            },
                            $value
                        );
                    },
                    explode("\t", rtrim($raw_response = fgets($h), "\r\n"))
                );
                return $result;
            },
            function () use (&$raw_response) {
                return " => '{$raw_response}'";
            }
        );
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

    /**
     * @return string|null
     */
    public function getLastError() {
        return $this->last_error;
    }

    protected function ensureIndexIsOpened(HandlerSocket_Index $Index) {
        if (!isset($this->indexes[$Index->getIndexId()])) {
            $Index->connect();
            $this->indexes[$Index->getIndexId()] = $Index;
        }
    }

    protected function h() {
        if (null === $this->socket) {
            $this->connect();
        }
        return $this->socket;
    }

    protected function connect() {
        $this->socket = $this->develCollectExecutionTime(
            function () {
                return 'connect: ' . $this->Dsn;
            },
            function () {
                if ($this->Dsn->has('socket')) {
                    $socket = fsockopen('unix://' . $this->Dsn->socket, null, $errno, $errstr);
                } else {
                    $socket = fsockopen(
                        $this->Dsn->get('hostname') ? : 'localhost',
                        $this->Dsn->get('port') ? : static::DEFAULT_PORT,
                        $errno,
                        $errstr,
                        static::CONN_TIMEOUT
                    );
                }
                if (!$socket) {
                    throw new \RuntimeException("Connection to " . $this->Dsn . " failed: error #$errno '$errstr'");
                }
                return $socket;
            }
        );
        stream_set_timeout($this->socket, static::SOCK_TIMEOUT, static::SOCK_TIMEOUT_MS);
    }

    protected function develGetTimerGroup() {
        return 'hs';
    }

    protected function develGetTimerMessage($message) {
        return sprintf('[%s] %s', $this->getConnectionId(), $message);
    }

}
