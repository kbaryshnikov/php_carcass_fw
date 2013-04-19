<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Mysql;
use Carcass\Connection;
use Carcass\Corelib;

/**
 * Shard Mysql QueryTemplate
 * @package Carcass\Shard
 */
class Mysql_QueryTemplate extends Mysql\QueryTemplate {

    protected
        $db_name,
        $shard_id,
        $unit_key,
        $unit_id;

    /**
     * @param Mysql_QueryParser $QueryParser
     * @param string $template
     */
    public function __construct(Mysql_QueryParser $QueryParser, $template) {
        $Unit           = $QueryParser->getClient()->getUnit();
        $this->unit_key = $Unit->getKey();
        $this->unit_id  = $Unit->getId();

        $Shard          = $Unit->getShard();
        $this->shard_id = $Shard->getId();
        $this->db_name  = $Shard->getDatabaseName();

        parent::__construct($QueryParser, $template);

        $this->registerGlobals(
            [
                $this->unit_key => $this->unit_id,
                '_unit_key'     => $this->unit_key,
                '_unit_id'      => $this->unit_id,
            ]
        );
    }

    /**
     * Expands to $alias1.unit_key = $alias2.unit_key
     *
     * @param string $alias1 table 1
     * @param string $alias2 table 2
     * @return string
     */
    public function on($alias1, $alias2) {
        return join(' = ', [
            $this->name("${alias1}.{$this->unit_key}"),
            $this->name("${alias2}.{$this->unit_key}"),
        ]);
    }

    /**
     * Expands to SET unit_key = unit_value, ... ,
     * @return string
     */
    public function set() {
        $table_aliases = func_get_args();
        return 'SET ' . join(', ', $this->buildUnitCond($table_aliases)) . ', ';
    }

    /**
     * Expands to WHERE unit_key = unit_value AND ... AND
     * @return string
     */
    public function where() {
        $table_aliases = func_get_args();
        return 'WHERE ' . join(' AND ', $this->buildUnitCond($table_aliases)) . ' AND ';
    }

    /**
     * @param $table_name
     * @return string full name-escaped database and table of the shard
     */
    public function t($table_name) {
        return $this->name($this->db_name . '.' . $table_name . $this->shard_id);
    }

    /**
     * @param array $table_aliases
     * @return array
     */
    protected function buildUnitCond(array $table_aliases = []) {
        if (empty($table_aliases)) {
            $result = [$this->name($this->unit_key) . '=' . $this->i($this->unit_id)];
        } else {
            $result = [];
            foreach ($table_aliases as $table_alias) {
                $result[] = $this->name($table_alias . '.' . $this->unit_key) . '=' . $this->i($this->unit_id);
            }
        }
        return $result;
    }


}
