<?php

namespace Carcass\Shard;

interface DsnMapperInterface {

    public function getDsn(UnitInterface $Unit);

}
