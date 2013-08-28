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
 * MySQL query template parser
 *
 * @package Carcass\Mysql
 */
class QueryTemplate extends Database\QueryTemplate {

    protected $ansi_name_escape_mode = false;

    /**
     * @param $limit
     * @param $offset
     * @return string
     */
    public function limit($limit, $offset = 0) {
        $tokens = [];
        if ($limit > 0) {
            $tokens[] = 'LIMIT ' . $this->lim($limit);
        } elseif ($offset > 0) {
            $tokens[] = 'LIMIT 18446744073709551615';
        }
        if ($offset > 0) {
            $tokens[] = 'OFFSET ' . $this->lim($offset);
        }
        return join(' ', $tokens);
    }

    public function setAnsiNameEscaping($bool = true) {
        $this->ansi_name_escape_mode = (bool)$bool;
        return $this;
    }

    protected function escapeNamePart($s) {
        if ($this->ansi_name_escape_mode) {
            return parent::escapeNamePart($s);
        }
        return '`' . str_replace('`', '``', $s) . '`';
    }

}
