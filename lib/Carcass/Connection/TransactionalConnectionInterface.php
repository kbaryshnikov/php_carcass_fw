<?php

namespace Carcass\Connection;

interface TransactionalConnectionInterface extends ConnectionInterface {

    public function setManager(Manager $Manager);

    public function begin($local = false);

    public function commit($local = false);

    public function rollback($local = false);

    public function doInTransaction(Callable $fn);

}

