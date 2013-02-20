<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Connection;
use Carcass\Corelib;

/**
 * Shard Database Server value-object and DSN builder
 *
 * @package Carcass\Shard
 */
class Mysql_Server extends Corelib\Hash {

    protected $cache = [];

    /**
     * @param bool $super whether to get superuser DSN
     * @param string|null $dbname get DSN with database name $dbname
     * @return Connection\Dsn
     */
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

    /**
     * Get superuser DSN
     * @return Connection\Dsn
     */
    public function getSuperDsn() {
        return $this->getDsn(true);
    }

    /**
     * @return $this
     */
    public function taint() {
        $this->cache = [];
        return parent::taint();
    }

}
