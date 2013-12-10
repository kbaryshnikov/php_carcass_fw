<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Database;

use Carcass\Connection\Dsn;
use Carcass\Connection\TransactionalConnectionTrait;
use Carcass\DevTools;
use Carcass\Corelib;

/**
 * Abstract Database Connection
 * @package Carcass\Database
 */
abstract class Connection {
    use TransactionalConnectionTrait;
    use DevTools\TimerTrait;
    use Corelib\UniqueObjectIdTrait {
        Corelib\UniqueObjectIdTrait::getUniqueObjectId as getConnectionId;
    }

    const DSN_TYPE = null;

    /**
     * @var \Carcass\Connection\Dsn
     */
    protected $Dsn;

    /**
     * @return Client
     */
    abstract public function assembleClient();

    /**
     * @param string $s
     * @return string
     */
    abstract public function escapeString($s);

    /**
     * @param $result
     * @return array|null
     */
    abstract public function fetch($result = null);

    /**
     * @param $result
     * @return $this
     */
    abstract public function freeResult($result = null);

    /**
     * @param $result
     * @return int
     */
    abstract public function getNumRows($result = null);

    /**
     * @param string|null $sequence
     * @return int|null
     */
    abstract public function getLastInsertId($sequence = null);

    /**
     * @return int
     */
    abstract public function getAffectedRows();

    abstract public function close();

    abstract protected function doExecuteQuery($query);

    /**
     * @param \Carcass\Connection\Dsn $Dsn
     */
    public function __construct(Dsn $Dsn) {
        Corelib\Assert::that('DSN has type ' . static::DSN_TYPE)->is(static::DSN_TYPE, $Dsn->getType());
        $this->Dsn = $Dsn;
    }

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
     * @return string|null
     */
    public function getCurrentDatabaseName() {
        return $this->Dsn->get('name') ? : null;
    }

    /**
     * @param $query
     * @return bool|\mysqli_result
     */
    public function executeQuery($query) {
        $this->triggerScheduledTransaction();
        return $this->doExecuteQuery($query);
    }

    protected function develGetTimerGroup() {
        return static::DSN_TYPE;
    }

    protected function develGetTimerMessage($message) {
        return sprintf('[%s] %s', $this->getConnectionId(), $message);
    }

}
