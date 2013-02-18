<?php

namespace Carcass\Shard;

use Carcass\Connection;
use Carcass\Corelib;

class Mysql_Server extends Corelib\Hash {

    protected
        $cache = [];

    public function getDsn($super = false, $dbname = null) {
        $super = (bool)$super;
        $this->untaint();

        if (!isset($this->cache[$super])) {
            if ($super) {
                $pw = [ 'user' => $this->super_username, 'password' => $this->super_password ];
            } else {
                $pw = [ 'user' => $this->username, 'password' => $this->password ];
            }
            $this->cache[$super] = Connection\Dsn::constructByTokens(new Corelib\Hash($pw + [
                'type'      => 'mysql',
                'hostname'  => $this->ip_address,
                'port'      => $this->port ?: null,
            ]));
        }

        $Dsn = $this->cache[$super];

        if ($dbname) {
            $Dsn->name = $dbname;
        }

        return $Dsn;
    }

    public function getSuperDsn() {
        return $this->getDsn(true);
    }

    public function taint() {
        $this->cache = [];
        return parent::taint();
    }

}
