<?php

namespace Carcass\Application;

use Carcass\Corelib as Corelib;

class Request extends Corelib\Hash {

    public function __construct($init_with = null) {
        parent::__construct($init_with);
        $this->untaint()->lock();
    }

}
