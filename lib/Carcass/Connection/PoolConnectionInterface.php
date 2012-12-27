<?php

namespace Carcass\Connection;

interface PoolConnectionInterface extends ConnectionInterface {

    public static function constructWithPool(DsnPool $DsnPool);

}
