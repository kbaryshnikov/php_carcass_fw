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
 *
 * @method \Carcass\Shard\Query getQuery()
 *
 * @package Carcass\Shard
 */
class Model extends MemcachedModel {

    /**
     * @var UnitInterface
     */
    protected $Unit;

    /**
     * @var array|null [ sequence name => sequence field ]
     */
    protected static $sequence = null;

    /**
     * @param UnitInterface $Unit
     */
    public function __construct(UnitInterface $Unit) {
        $this->Unit = $Unit;
        parent::__construct();
    }

    /**
     * @param string $query
     * @param array $args
     * @return mixed
     */
    protected function doInsert($query, array $args = []) {
        if (!$this->validate()) {
            return false;
        }
        $result = $this->getQuery()->insert(
            $query,
            $args + $this->exportArray(),
            $this->getSequence()
        );
        $result and $this->setSequenceFieldValue($result);
        return $result;
    }

    /**
     * @return Query
     */
    protected function createQueryInstance() {
        return new Query($this->Unit);
    }

    protected function setSequenceFieldValue($value) {
        if ($value) {
            $sequence = $this->getSequence();
            if ($sequence) {
                $this->getFieldset()->set(reset($sequence), $value);
            }
        }
    }

    /**
     * @return array|null [ sequence name => sequence field ]
     */
    protected function getSequence() {
        return static::$sequence;
    }

}
