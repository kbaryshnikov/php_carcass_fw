<?php

namespace Carcass\Shard;

interface AllocatorInterface {

    /// @return int $shard_id
    public function allocate(UnitInterface $Unit);

}
