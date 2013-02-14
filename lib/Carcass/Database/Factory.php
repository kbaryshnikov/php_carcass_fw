<?php

namespace Carcass\Database;

class Factory {

    protected static $map = [
        'mysql' => '\Carcass\Mysql\Database',
        'mysqls' => '\Carcass\Shard\Mysql_Database',
    ];

    public static function register($protocol, $implementation) {
        static::$map[$protocol] = $implementation;
        return $this;
    }

    public static function assemble($Connection) {
        $protocol = $Connection->getDsn()->getType();
        if (!isset(static::$map[$protocol])) {
            throw new \LogicException("No implementation is known for '$protocol'");
        }
        $class = static::$map[$protocol];
        return new $class($Connection);
    }

}
