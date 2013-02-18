<?php

namespace Carcass\Shard;

use Carcass\Mysql;

class Mysql_QueryParser extends Mysql\QueryParser {

    public function getTemplate($template) {
        return new Mysql_QueryTemplate($this, $template);
    }

}
