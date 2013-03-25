<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

/**
 * Shard Interface
 * @package Carcass\Shard
 */
interface ShardInterface {

    /**
     * @return int
     */
    public function getId();

    /**
     * @return \Carcass\Connection\Dsn
     */
    public function getDsn();

    /**
     * @return string
     */
    public function getDatabaseName();

    /**
     * @return mixed
     */
    public function getServer();

}
