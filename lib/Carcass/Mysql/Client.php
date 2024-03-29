<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mysql;

use Carcass\Database;

/**
 * MySQL Client
 *
 * @method Client selectDatabase($db_name)
 *
 * @package Carcass\Mysql
 */
class Client extends Database\Client {

    /**
     * @return QueryParser
     */
    protected function assembleDefaultQueryParser() {
        return new QueryParser($this);
    }

}
