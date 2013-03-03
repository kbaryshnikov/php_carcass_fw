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
     * @param string $sql_query_template
     * @param array $args
     * @param array|null sequence  [ 'sequence_name' => 'field_name' ]
     * @return mixed
     */
    public function insert($sql_query_template, array $args = [], array $sequence = null) {
        $this->doModify(
            function (Mysql_Client $Db, $args) use ($sql_query_template, $sequence) {
                $sequence_value = null;
                if ($sequence) {
                    reset($sequence);
                    list ($sequence_name, $sequence_field_name) = each($sequence);
                    $this->last_insert_id_field_name = $sequence_field_name;
                    $sequence_value = $args[$sequence_field_name] = $Db->getSequenceNextValue($sequence_name);
                }
                $affected_rows        = $Db->query($sql_query_template, $this->getArgs($args));
                $this->last_insert_id = $sequence_value;
                return $affected_rows;
            }, $args, !empty($sequence)
        );
        return $this->last_insert_id;
    }

    /**
     * @param array $options
     * @return \Carcass\Memcached\TaggedCache
     */
    protected function assembleMct(array $options = []) {
        return parent::assembleMct(
            $options + [
                'prefix' => $this->Unit->getKey() . '_' . $this->Unit->getId() . '|',
            ]
        );
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
