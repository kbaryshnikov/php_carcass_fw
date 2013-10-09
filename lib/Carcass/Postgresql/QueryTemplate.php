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
 * PostgreSQL Query template parser
 *
 * @package Carcass\Postgresql
 */
class QueryTemplate extends Database\QueryTemplate {

    protected $datetime_format = 'Y-m-d H:i:s+00';

}
