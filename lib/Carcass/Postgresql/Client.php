<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Postgresql;

use Carcass\Database;

/**
 * PostgreSQL Client
 *
 * @package Carcass\Postgresql
 */
class Client extends Database\Client {

    /**
     * @return QueryParser
     */
    protected function assembleDefaultQueryParser() {
        return new QueryParser($this);
    }

}
