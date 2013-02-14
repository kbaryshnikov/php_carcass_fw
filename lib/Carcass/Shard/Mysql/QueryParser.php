<?php

namespace Carcass\Shard;

use Carcass\Mysql;
use Carcass\Connection;
use Carcass\Corelib;

class Mysql_QueryParser extends Mysql\QueryParser {

    protected
        $shard_id,
        $unit_key,
        $unit_id;

    public function __construct(Mysql_Connection $Connection, $template) {
        parent::__construct($Connection, $template);

        $shard_id = $Connection->getDsn()->args->get('shard_id');
        Corelib\Assert::isValidId($shard_id);
        $this->shard_id = $shard_id;
        
        $Unit = $Connection->getShardUnit();
        $this->unit_key = $Unit->getKey();
        $this->unit_id = $Unit->getId();
        $this->setGlobals([
            $this->unit_key => $this->unit_id,
            '_unit_key' => $this->unit_key,
            '_unit_id'  => $this->unit_id,
        ]);
    }

    public function set() {
        $table_aliases = func_get_args();
        return 'SET ' . join(', ', $this->buildUnitCond($table_aliases)) . ', ';
    }

    public function where() {
        $table_aliases = func_get_args();
        return 'WHERE ' . join(' AND ', $this->buildUnitCond($table_aliases)) . ' AND ';
    }

    public function buildUnitCond(array $table_aliases = []) {
        if (empty($table_aliases)) {
            $result = [ $this->name($this->unit_key) . '=' . $this->i($this->unit_id) ];
        } else {
            $result = [];
            foreach ($table_aliases as $table_alias) {
                $result[] = $this->name($table_alias . '.' . $this->unit_key) . '=' . $this->i($this->unit_id);
            }
        }
        return $result;
    }

    public function t($table_name) {
        return $table_name . $this->shard_id;
    }

}
