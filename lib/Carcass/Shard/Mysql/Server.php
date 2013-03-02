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
                $pw = ['user' => $this->super_username, 'pass' => $this->super_password];
            } else {
                $pw = ['user' => $this->username, 'pass' => $this->password];
            }
            $this->cache[$super] = Connection\Dsn::constructByTokensArray(
                $pw + [
                    'scheme' => 'mysql',
                    'host'   => $this->ip_address,
                    'port'   => $this->port ? : null,
                ]
            );
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
     * @return int
     * @throws \LogicException
     */
    public function getId() {
        $server_id = $this->get('database_server_id');
        if (!$server_id) {
            throw new \LogicException('database_server_id is undefined');
        }
        return (int)$server_id;
    }

    /**
     * @return $this
     */
    public function taint() {
        $this->cache = [];
        return parent::taint();
    }

}
