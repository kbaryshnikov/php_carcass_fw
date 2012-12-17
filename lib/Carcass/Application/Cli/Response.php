<?php

namespace Carcass\Application;

class Cli_Response extends Response {

    protected $status = 0;

    public function setStatus($status) {
        parent::setStatus(intval($status));
        return $this;
    }

}
