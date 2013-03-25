<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

/**
 * Trait with common methods for shard models
 *
 * @package Carcass\Shard
 */
trait ModelTrait {

    /**
     * @var UnitInterface
     */
    protected $Unit;

    /**
     * @param UnitInterface $Unit
     * @return $this
     */
    protected function setUnit(UnitInterface $Unit) {
        $this->Unit = $Unit;
        return $this;
    }

    /**
     * @return Query
     */
    protected function createQueryInstance() {
        return new Query($this->Unit);
    }

}