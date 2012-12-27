<?php

namespace Carcass\Connection;

class Manager {

    protected $dsn_map = [
        'memcached' => '\Carcass\Memcached\Connection',
        'mysql'     => '\Carcass\Database\Mysql_Connection',
        'hs'        => '\Carcass\Database\HandlerSocket_Connection',
        'pgsql'     => '\Carcass\Database\Pgsql_Connection',
    ];

    protected $registry = [];

    public function registerDsn($name, $class) {
        $this->dsn_map[$name] = $class;
        return $this;
    }

    public function registerDsns(array $map) {
        $this->dsn_map = $map + $dsn_map;
        return $this;
    }

    public function getConnectionByDsnString($dsn_string) {
        return $this->getConnectionByDsn(Dsn::factory($dsn));
    }

    public function getConnectionByDsn($Dsn) {
        $normalized_string_dsn = (string)$Dsn;
        if (!isset($this->registry[$normalized_string_dsn])) {
            $this->registry[$normalized_string_dsn] = $this->assembleConnection($dsn_string);
        }
        return $this->registry[$normalized_string_dsn];
    }

    public function forEachConection(Callable $fn) {
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

    protected function assembleConnection($dsn_string) {
        $type = $Dsn->getType();
        if (!isset($this->dsn_map[$type])) {
            throw new \LogicException("Do not know how to assemble an instance of '$type' connection");
        }
        $class = $this->dsn_map[$type];
        if ($Dsn instanceof DsnPool) {
            if ($class instanceof PoolConnectionInterface) {
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
