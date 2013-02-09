<?php

namespace Carcass\Shard;

use Carcass\Mysql;
use Carcass\Connection;
use Carcass\Corelib;

class Mysql_QueryParser extends Mysql\QueryParser {

    protected
        $shard_id;

    public function __construct(Connection $Connection, Unit $Unit, $template) {
        parent::__construct($Connection, $template);
        $shard_id = $Connection->getDsn()->args->get('shard_id');
        Corelib\Assert::isValidId($shard_id);
        $this->shard_id = $shard_id;
    }

    public function t($table_name) {
        return $table_name . $this->shard_id;
    }

}
