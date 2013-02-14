<?php

namespace Carcass\Shard;

use Carcass\Mysql;
use Carcass\Connection;
use Carcass\Corelib;

class Mysql_QueryParser extends Mysql\QueryParser {

    protected
        $shard_id;

    public function __construct(Connection $Connection, $template) {
        parent::__construct($Connection, $template);

        $shard_id = $Connection->getDsn()->args->get('shard_id');
        Corelib\Assert::isValidId($shard_id);
        $this->shard_id = $shard_id;
        
        $Unit = $Connection->getShardUnit();
        $this->setGlobals([$Unit->getKey() => $Unit->getId()]);
    }

    public function t($table_name) {
        return $table_name . $this->shard_id;
    }

}
