<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Model\MemcachedList;

abstract class ListModel extends MemcachedList {
    use ModelTrait;

    /**
     * @param UnitInterface $Unit
     */
    public function __construct(UnitInterface $Unit) {
        $this->setUnit($Unit);
    }

    /**
     * @return Model
     */
    protected function constructItemModel() {
        $class = static::getItemModelClass();
        return new $class($this->Unit);
    }
}
