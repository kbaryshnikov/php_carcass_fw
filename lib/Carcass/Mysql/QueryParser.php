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
 * MySQL Query Parser
 * @package Carcass\Mysql
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
