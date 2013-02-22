<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Connection;

use Carcass\Corelib\UniqueId;

/**
 * Connections and transactions manager.
 * Registered in Injector as ConnectionManager during the application instance bootstrapping process.
 *
 * @package Carcass\Connection
 */
class Manager {

    protected $dsn_type_map = [
        'memcached' => '\Carcass\Memcached\Connection',
        'mysql'     => '\Carcass\Mysql\Connection',
        'hs'        => '\Carcass\Mysql\HandlerSocket_Connection',
    ];

    protected $registry = [];

    /**
     * @var string|null
     */
    protected $transaction_id = null;

    /**
     * Every transaction must have an unique ID assigned by its starter.
     * Returns that ID, or null if there's no transaction active.
     *
     * @return string|null
     */
    public function getTransactionId() {
        return $this->transaction_id;
    }

    /**
     * Sets the transaction ID. Connections must call this method when starting a transaction
     * (e.g. by using the TransactionalConnectionTrait).
     *
     * @param string $transaction_id
     * @return $this
     */
    public function setTransactionId($transaction_id) {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    /**
     * Registers an extra dsn type $name for factory methods.
     *
     * @param string $name DSN type
     * @param string $class implementation class
     * @return $this
     */
    public function registerType($name, $class) {
        $this->dsn_type_map[$name] = $class;
        return $this;
    }

    /**
     * Registers extra dsn types.
     *
     * @param array $map array of (name => class) - see the registerType() method
     * @return $this
     */
    public function registerTypes(array $map) {
        $this->dsn_type_map = $map + $this->dsn_type_map;
        return $this;
    }

    /**
     * Replaces current dsn type map with $map. N.B.: removes the default mapping.
     *
     * @param array $map array of (name => class) - see the registerType() method
     * @return $this
     */
    public function replaceTypes(array $map) {
        $this->dsn_type_map = $map;
        return $this;
    }

    /**
     * Factory method. Returns connection by $dsn. Connections with same dsns are cached,
     * so there is always one connection for one DSN.
     *
     * @param mixed $dsn Dsn, DsnPool, or any value good for Dsn::factory
     * @return ConnectionInterface
     */
    public function getConnection($dsn) {
        if ($dsn instanceof DsnInterface) {
            $Dsn = $dsn;
        } else {
            $Dsn = Dsn::factory($dsn);
        }
        return $this->getConnectionByDsn($Dsn);
    }

    /**
     * Factory method. Returns connection by $Dsn. Connections with same dsns are cached,
     * so there is always one connection for one DSN.
     *
     * @param DsnInterface $Dsn
     * @return ConnectionInterface
     */
    public function getConnectionByDsn(DsnInterface $Dsn) {
        $normalized_string_dsn = (string)$Dsn;
        if (!isset($this->registry[$normalized_string_dsn])) {
            $this->registry[$normalized_string_dsn] = $this->assembleConnection($Dsn);
        }
        return $this->registry[$normalized_string_dsn];
    }

    /**
     * Registers $Connection in manager.
     *
     * @param ConnectionInterface $Connection
     * @return $this
     * @throws \LogicException
     */
    public function addConnection(ConnectionInterface $Connection) {
        $normalized_string_dsn = (string)$Connection->getDsn();
        if (isset($this->registry[$normalized_string_dsn])) {
            throw new \LogicException("Connectino with DSN '$normalized_string_dsn' is already registered'");
        }
        $this->registry[$normalized_string_dsn] = $Connection;
        return $this;
    }

    /**
     * Calls $fn for each connection in the registry, as $fn($Connection, $connection_dsn)
     *
     * @param callable $fn
     * @return $this
     */
    public function forEachConnection(Callable $fn) {
        foreach ($this->registry as $dsn => $Connection) {
            $fn($Connection, $dsn);
        }
        return $this;
    }

    /**
     * Calls $fn for each transactional connection in the registry, as $fn($Connection, $connection_dsn)
     *
     * @param callable $fn
     * @param null|bool $xa: true = XA connections only, false = non-XA connections only, null = any transactional connection
     * @return $this
     */
    public function forEachTransactionalConnection(Callable $fn, $xa = null) {
        foreach ($this->registry as $dsn => $Connection) {
            if ($Connection instanceof TransactionalConnectionInterface) {
                if (null === $xa || $xa === $Connection instanceof XaTransactionalConnectionInterface) {
                    $fn($Connection, $dsn);
                }
            }
        }
        return $this;
    }

    /**
     * Calls $fn inside a transaction.
     *
     * @param callable $fn               callback to run inside a started transaction
     * @param array $args                $fn callback arguments
     * @param callable|null $finally_fn  "finally" callback
     * @return mixed the $fn result
     * @throws \Exception                rollbacks if $fn throws a transaction, and throws it again after the "finally" code
     */
    public function doInTransaction(Callable $fn, array $args = [], Callable $finally_fn = null) {
        $e = null;
        try {
            $this->begin();
            $result = call_user_func_array($fn, $args);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
        }
        if (null !== $finally_fn) {
            $finally_fn($result);
        }
        if (null !== $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * Starts transaction for all transactional connections
     *
     * @param TransactionalConnectionInterface $Source
     * @return $this
     */
    public function begin(TransactionalConnectionInterface $Source = null) {
        $this->iterateTransactionMethod('begin', $Source);
        return $this;
    }

    /**
     * Commits transaction for all transactional connections
     *
     * @param TransactionalConnectionInterface|null $Source
     * @throws ManagerXaVotedNoException|\Exception
     * @return $this
     */
    public function commit(TransactionalConnectionInterface $Source = null) {
        try {
            $this->forEachTransactionalConnection(
                function ($Connection) use ($Source) {
                    /** @var XaTransactionalConnectionInterface $Connection */
                    try {
                        if (!$Connection->vote(true)) {
                            throw new ManagerXaVotedNoException;
                        }
                    } catch (\Exception $e) {
                        throw new ManagerXaVotedNoException($e->getMessage(), $e->getCode(), $e);
                    }
                }
            );
        } catch (ManagerXaVotedNoException $e) {
            $this->rollback();
            throw $e;
        }
        $this->iterateTransactionMethod('commit', $Source);
        return $this;
    }

    /**
     * Rollbacks transaction for all transactional connections
     *
     * @param TransactionalConnectionInterface $Source
     * @return $this
     */
    public function rollback(TransactionalConnectionInterface $Source = null) {
        $this->iterateTransactionMethod('rollback', $Source);
        return $this;
    }

    /**
     * @param string $method
     * @param TransactionalConnectionInterface $Source
     * @param null|bool $xa: true = XA connections only, false = non-XA connections only, null = any transactional connection
     */
    protected function iterateTransactionMethod($method, TransactionalConnectionInterface $Source = null, $xa = null) {
        $this->forEachTransactionalConnection(
            function ($Connection) use ($method, $Source) {
                if ($Connection !== $Source) {
                    $Connection->$method(true);
                }
            }
        );
    }

    /**
     * @param DsnInterface $Dsn
     * @return TransactionalConnectionInterface
     * @throws \LogicException
     */
    protected function assembleConnection(DsnInterface $Dsn) {
        $type = $Dsn->getType();
        if (!isset($this->dsn_type_map[$type])) {
            throw new \LogicException("Do not know how to assemble an instance of '$type' connection");
        }
        /** @var ConnectionInterface $class */
        $class = $this->dsn_type_map[$type];
        if ($Dsn instanceof DsnPool) {
            if (array_key_exists(__NAMESPACE__ . '\PoolConnectionInterface', class_implements($class))) {
                /** @var PoolConnectionInterface $class */
                $class      = $this->dsn_type_map[$type];
                $Connection = $class::constructWithPool($Dsn);
            } else {
                throw new \LogicException("'$type' connection does not support pools, but pool dsn given");
            }
        } elseif ($Dsn instanceof Dsn) {
            $Connection = $class::constructWithDsn($Dsn);
        } else {
            throw new \LogicException("Not implemented for " . get_class($Dsn));
        }
        if ($Connection instanceof TransactionalConnectionInterface) {
            $Connection->setManager($this);
        }
        return $Connection;
    }

}

class ManagerXaVotedNoException extends \LogicException {
    // pass
}

;
