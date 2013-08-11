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
 * PostgreSQL Query Parser
 *
 * @package Carcass\Postgresql
 */
class QueryParser extends Database\QueryParser {

    /**
     * @param $template
     * @return QueryTemplate
     */
    public function getTemplate($template) {
        return new QueryTemplate($this, $template);
    }

}
