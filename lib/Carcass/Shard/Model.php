<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use \Carcass\Model\Memcached as MemcachedModel;

/**
 * Sharded model
 * @package Carcass\Shard
 */
class Model extends MemcachedModel {

    /**
     * @var UnitInterface
     */
    protected $Unit;

    /**
     * @param UnitInterface $Unit
     */
    public function __construct(UnitInterface $Unit) {
        $this->Unit = $Unit;
        parent::__construct();
    }

    /**
     * @return Query
     */
    protected function createQueryInstance() {
        return new Query($this->Unit);
    }

}
