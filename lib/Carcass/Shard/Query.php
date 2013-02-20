<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Query\Memcached as MemcachedQuery;

/**
 * Sharded Query
 * @package Carcass\Shard
 */
class Query extends MemcachedQuery {

    /**
     * @var UnitInterface
     */
    protected $Unit;

    /**
     * @param UnitInterface $Unit
     */
    public function __construct(UnitInterface $Unit) {
        $this->Unit = $Unit;
    }

    /**
     * @param array $options
     * @return \Carcass\Memcached\TaggedCache
     */
    protected function assembleMct(array $options = []) {
        return parent::assembleMct($options + [
            'prefix' => $this->Unit->getKey() . '_' . $this->Unit->getId() . '|',
        ]);
    }

    /**
     * @return Mysql_Client
     */
    protected function assembleDatabaseClient() {
        return $this->Unit->getDatabase();
    }

    /**
     * @return \Carcass\Memcached\Connection
     */
    protected function assembleMemcachedConnection() {
        return $this->Unit->getMemcachedConnection();
    }

}
