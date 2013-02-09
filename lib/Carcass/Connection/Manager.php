<?php

namespace Carcass\Connection;

use Carcass\Corelib\UniqueId;

class Manager {

    protected $dsn_type_map = [
        'memcached' => '\Carcass\Memcached\Connection',
        'mysql'     => '\Carcass\Mysql\Connection',
        'hs'        => '\Carcass\HandlerSocket\Connection',
    ];

    protected $registry = [];

    protected $transaction_id = null;

    public function getTransactionId() {
        return $this->transaction_id;
    }

    public function setTransactionId($transaction_id) {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    public function registerType($name, $class) {
        $this->dsn_type_map[$name] = $class;
        return $this;
    }

    public function registerTypes(array $map) {
        $this->dsn_type_map = $map + $this->dsn_type_map;
        return $this;
    }

    public function replaceTypes(array $map) {
        $this->dsn_type_map = $map;
        return $this;
    }

    public function getConnection($dsn) {
        if ($dsn instanceof Dsn || $dsn instanceof DsnPool) {
            $Dsn = $dsn;
        } else {
            $Dsn = Dsn::factory($dsn);
        }
        return $this->getConnectionByDsn($Dsn);
    }

    public function getConnectionByDsn($Dsn) {
        $normalized_string_dsn = (string)$Dsn;
        if (!isset($this->registry[$normalized_string_dsn])) {
            $this->registry[$normalized_string_dsn] = $this->assembleConnection($Dsn);
        }
        return $this->registry[$normalized_string_dsn];
    }

    public function forEachConnection(Callable $fn) {
        foreach ($this->registry as $dsn => $Connection) {
            $fn($Connection, $dsn);
        }
        return $this;
    }

    public function forEachTransactionalConnection(Callable $fn) {
        foreach ($this->registry as $dsn => $Connection) {
            if ($Connection instanceof TransactionalConnectionInterface) {
                $fn($Connection, $dsn);
            }
        }
        return $this;
    }

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

    public function begin(TransactionalConnectionInterface $Source = null) {
        $this->iterateTransactionMethod('begin', $Source);
        return $this;
    }

    public function commit(TransactionalConnectionInterface $Source = null) {
        $this->iterateTransactionMethod('commit', $Source);
        return $this;
    }

    public function rollback(TransactionalConnectionInterface $Source = null) {
        $this->iterateTransactionMethod('rollback', $Source);
        return $this;
    }

    protected function iterateTransactionMethod($method, TransactionalConnectionInterface $Source = null) {
        $this->forEachTransactionalConnection(function($Connection) use ($method, $Source) {
            if ($Connection !== $Source) {
                $Connection->$method(true);
            }
        });
    }

    protected function assembleConnection($Dsn) {
        $type = $Dsn->getType();
        if (!isset($this->dsn_type_map[$type])) {
            throw new \LogicException("Do not know how to assemble an instance of '$type' connection");
        }
        $class = $this->dsn_type_map[$type];
        if ($Dsn instanceof DsnPool) {
            if (array_key_exists(__NAMESPACE__ . '\PoolConnectionInterface', class_implements($class))) {
                $Connection = $class::constructWithPool($Dsn);
            } else {
                throw new \LogicException("'$type' connection does not support pools, but pool dsn given");
            }
        } else {
            $Connection = $class::constructWithDsn($Dsn);
        }
        if ($Connection instanceof TransactionalConnectionInterface) {
            $Connection->setManager($this);
        }
        return $Connection;
    }

}
