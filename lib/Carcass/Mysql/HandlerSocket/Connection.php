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

    public function disconnect($for_reconnect = false) {
        if ($this->socket !== null) {
            @fclose($this->socket);
            if (!$for_reconnect) {
                $this->next_dbname = null;
            }
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
     * @param int $max_attempts_on_error
     * @throws \LogicException
     * @throws \RuntimeException
     * @internal param int $max_attempts
     * @return array|bool
     */
    public function query(array $tokens, $Index = null, $max_attempts_on_error = 10) {
        $attempts = 0;
        $error = null;
        do {
            if ($error) {
                $this->disconnect(true);
            }
            if ($Index) {
                $this->ensureIndexIsOpened($Index);
            }
            $query = join("\t", $tokens);
            $h = $this->h();
            $raw_response = null;
            $error = null;
            $result = $this->develCollectExecutionTime(
                $query,
                function () use ($h, $query, &$raw_response, &$error) {
                    $hs_line = $query . "\n";
                    $hs_line_len = strlen($hs_line);
                    $bytes_written = fwrite($h, $hs_line);
                    $hs_response = null;
                    $result = null;
                    if (false === $bytes_written || $hs_line_len != $bytes_written) {
                        $error = 'Write failed';
                    } else {
                        $hs_response = stream_get_line($h, 1048576, "\n");
                        if ($hs_response === "" || $hs_response === false) {
                            $error = 'Read failed';
                        } else {
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
                                explode("\t", rtrim($hs_response, "\n"))
                            );
                            if (isset($result[0]) && $result[0] == 1 && isset($result[2]) && $result[2] === 'lock_tables') {
                                $result = null;
                                $error = 'Table is locked';
                                usleep(10);
                            }
                        }
                    }
                    return $result;
                },
                function () use (&$raw_response, $error) {
                    return " => '{$raw_response}'" . ($error ? " [!] $error" : '');
                }
            );
        } while ($error && $attempts++ < $max_attempts_on_error);
        if (!is_array($result) || !isset($result[0])) {
            if ($error) {
                throw new \LogicException("HandlerSocket error: " . $error);
            } else {
                throw new \LogicException("Malformed HandlerSocket response: " . $raw_response);
            }
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
                    $conn_timeout = $this->getConnTimeout();
                    $socket = fsockopen(
                        $this->Dsn->get('hostname') ? : 'localhost',
                        $this->Dsn->get('port') ? : static::DEFAULT_PORT,
                        $errno,
                        $errstr,
                        $conn_timeout
                    );
                }
                if (!$socket) {
                    throw new \RuntimeException("Connection to " . $this->Dsn . " failed: error #$errno '$errstr'");
                }
                return $socket;
            }
        );
        $this->setSockTimeout();
    }

    protected function getConnTimeout() {
        return $this->Dsn->args->get('conn_timeout', self::CONN_TIMEOUT);
    }

    protected function setSockTimeout() {
        $sock_timeout = $this->Dsn->args->get('sock_timeout', self::SOCK_TIMEOUT);
        $sock_timeout_ms = $this->Dsn->args->get('sock_timeout_ms', self::SOCK_TIMEOUT_MS);
        stream_set_timeout($this->socket, $sock_timeout, $sock_timeout_ms);
    }

    protected function develGetTimerGroup() {
        return 'hs';
    }

    protected function develGetTimerMessage($message) {
        return sprintf('[%s] %s', $this->getConnectionId(), $message);
    }

}
