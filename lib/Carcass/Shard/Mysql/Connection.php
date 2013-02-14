<?php

namespace Carcass\Shard;

use Carcass\Mysql;

class Mysql_Connection extends Mysql\Connection {

    protected $Unit = null;

    public function setShardUnit(Unit $Unit) {
        $this->Unit = $Unit;
        return $this;
    }

    public function getShardUnit() {
        if (null === $this->Unit) {
            throw new \LogicException('Shard unit is undefined');
        }
        return $this->Unit;
    }

    public function getQueryParser($template) {
        return new Mysql_QueryParser($this, $template);
    }

}
