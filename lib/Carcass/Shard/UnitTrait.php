<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Application\DI;

/**
 * Partial UnitInterface implementation
 *
 * User must implement:
 * @method int getShardId()
 * @method void updateShard(ShardInterface $OldShard = null)
 * @method void initializeShard()
 *
 * @package Carcass\Shard
 */
trait UnitTrait {

    /**
     * @var ShardInterface
     */
    protected $Shard = null;
    /**
     * @var Mysql_Client
     */
    protected $DatabaseClient = null;
    /**
     * @var \Carcass\Memcached\Connection
     */
    protected $MemcachedConnection = null;

    /**
     * @return \Carcass\Shard\Mysql_Shard
     */
    public function getShard() {
        if (null === $this->Shard) {
            $this->Shard = $this->getShardManager()->getShardById($this->getShardId());
        }
        return $this->Shard;
    }

    /**
     * @param ShardInterface $Shard
     * @param bool $must_initialize_shard
     * @return ShardInterface
     */
    public function setShard(ShardInterface $Shard, $must_initialize_shard = false) {
        $OldShard = $this->Shard;
        $this->Shard = $Shard;
        if ($must_initialize_shard) {
            $this->initializeShard();
        }
        $this->updateShard($OldShard);
        return $this;
    }

    /**
     * Explicitly force the unit to use $Shard for all further operations.
     * Usable with empty units and shard operations which do not
     * require shard unit. With existing units it's dangerous and apparently
     * should never be used.
     *
     * @param ShardInterface $Shard
     * @return $this
     */
    public function forceShard(ShardInterface $Shard) {
        $this->Shard = $Shard;
        return $this;
    }

    /**
     * @return Mysql_Client
     */
    public function getDatabaseClient() {
        if (null === $this->DatabaseClient) {
            /** @noinspection PhpParamsInspection ($this will actually implement UnitInterface) */
            $this->DatabaseClient = new Mysql_Client($this);
        }
        return $this->DatabaseClient;
    }

    /**
     * @return Mysql_ShardManager
     */
    public function getShardManager() {
        static $ShardManager = null;
        if (null === $ShardManager) {
            $ShardManager = new Mysql_ShardManager(DI::getConfigReader()->get('sharding'));
        }
        return $ShardManager;
    }

    /**
     * @return \Carcass\Connection\ConnectionInterface
     */
    public function getMemcachedConnection() {
        if (null === $this->MemcachedConnection) {
            $this->MemcachedConnection = DI::getConnectionManager()
                ->getConnection(DI::getConfigReader()->getPath('memcached.pool'));
        }
        return $this->MemcachedConnection;
    }

    protected function allocateShardForMyself() {
        if (!$this instanceof UnitInterface) {
            throw new \LogicException(get_class($this) . ' does not implement UnitInterface');
        }
        return $this->getShardManager()->allocateShard($this);
    }

}
