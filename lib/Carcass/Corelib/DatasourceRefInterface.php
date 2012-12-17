<?php

namespace Carcass\Corelib;

interface DatasourceRefInterface extends DatasourceInterface {

    public function &getRef($key);

}

