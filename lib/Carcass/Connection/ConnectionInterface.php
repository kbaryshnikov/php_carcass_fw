<?php

namespace Carcass\Connection;

interface ConnectionInterface {

    public static function constructWithDsn(Dsn $Dsn);

}
