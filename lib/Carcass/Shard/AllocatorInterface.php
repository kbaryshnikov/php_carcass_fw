<?php

namespace Carcass\Shard;

interface AllocatorInterface {

    // sets $Unit->setShardId()
    public function allocate(UnitInterface $Unit);

}
