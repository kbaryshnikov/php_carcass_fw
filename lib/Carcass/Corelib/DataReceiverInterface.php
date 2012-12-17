<?php

namespace Carcass\Corelib;

interface DataReceiverInterface {

    public function fetchFrom(\Traversable $Source);

    public function fetchFromArray(array $source);

}
