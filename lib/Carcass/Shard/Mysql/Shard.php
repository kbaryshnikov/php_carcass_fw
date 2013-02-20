<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Corelib;
use Carcass\Application\Injector;

/**
 * Mysql Shard
 * @package Carcass\Shard
 */
class Mysql_Shard extends Corelib\Hash implements ShardInterface {

    /**
     * @var Mysql_Server
     */
    protected $Server = null;
    /**
     * @var Mysql_ShardManager
     */
    protected $ShardManager;

    /**
     * @param Mysql_ShardManager $ShardManager
     * @param array $shard_data
     */
    public function __construct(Mysql_ShardManager $ShardManager, array $shard_data = []) {
        $this->ShardManager = $ShardManager;
        parent::__construct($shard_data);
    }

    /**
     * @return int
     * @throws \LogicException
     */
    public function getId() {
        $shard_id = $this->get('database_shard_id');
        if (!$shard_id) {
            throw new \LogicException('database_shard_id is undefined');
        }
        return $shard_id;
    }

    /**
     * @return Mysql_Server
     * @throws \LogicException
     */
    public function getServer() {
        if (null === $this->Server) {
            $server_id = $this->get('database_server_id');
            if (!$server_id) {
                throw new \LogicException('database_server_id is undefined');
            }
            $this->Server = $this->ShardManager->getModel()->getServerById($server_id);
        }
        return $this->Server;
    }

    /**
     * @return \Carcass\Connection\Dsn
     */
    public function getDsn() {
        return $this->getServer()->getDsn();
    }

    /**
     * @return string
     */
    public function getDatabaseName() {
        return $this->ShardManager->getShardDbNameByIndex($this->database_idx);
    }

}
