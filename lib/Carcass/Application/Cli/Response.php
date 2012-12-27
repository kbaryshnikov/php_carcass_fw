<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Cli_Response extends Corelib\Response {

    protected $status = 0;

    public function setStatus($status) {
        parent::setStatus(intval($status));
        return $this;
    }

}
