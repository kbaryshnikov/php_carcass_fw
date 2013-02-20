<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Mysql;

/**
 * Shard MySQL QueryParser
 * @package Carcass\Shard
 */
class Mysql_QueryParser extends Mysql\QueryParser {

    /**
     * @param $template
     * @return Mysql_QueryTemplate
     */
    public function getTemplate($template) {
        return new Mysql_QueryTemplate($this, $template);
    }

    /**
     * @return Mysql_Client
     */
    public function getClient() {
        return parent::getClient();
    }

}
