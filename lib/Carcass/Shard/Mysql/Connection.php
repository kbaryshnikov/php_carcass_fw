<?php

namespace Carcass\Shard;

use Carcass\Mysql;

class Mysql_Connection extends Mysql\Connection {

    public function getQueryParser($template) {
        return new Mysql_QueryParser($this, $template);
    }

}
