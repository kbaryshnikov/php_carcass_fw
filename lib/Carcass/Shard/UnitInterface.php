<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

/**
 * Shard Unit Interface
 * @package Carcass\Shard
 */
interface UnitInterface {

    /**
     * @param $id
     * @return bool
     */
    public function loadById($id);

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getKey();

    /**
     * @return ShardInterface
     */
    public function getShard();

    /**
     * @param ShardInterface $Shard
     * @param bool $must_initialize_shard
     * @return $this
     */
    public function setShard(ShardInterface $Shard, $must_initialize_shard = false);

    /**
     * @return \Carcass\Memcached\Connection
     */
    public function getMemcachedConnection();

    /**
     * @return Mysql_Client
     */
    public function getDatabase();

    /**
     * @return Mysql_ShardManager
     */
    public function getShardManager();

}
