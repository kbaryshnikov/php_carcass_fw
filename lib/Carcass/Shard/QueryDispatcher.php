<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Query\MemcachedDispatcher;
use Carcass\Memcached;

/**
 * Sharded Query Dispatcher
 * @package Carcass\Shard
 */
class QueryDispatcher extends MemcachedDispatcher {

    const UNIT_MEMCACHED_PREFIX_TEMPLATE = '{{ unit_key }}_{{ i(unit_id) }}:';

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
     * @param array|string|null sequence  [ 'sequence_name' => 'field_name' ], or string if sequence_name == field_name
     * @return mixed
     */
    public function insert($sql_query_template, array $args = [], $sequence = null) {
        $this->doModify(
            function (Mysql_Client $Db, $args) use ($sql_query_template, $sequence) {
                $sequence_value = null;
                if ($sequence) {
                    if (!is_array($sequence)) {
                        $sequence_name = $sequence_field_name = (string)$sequence;
                    } else {
                        reset($sequence);
                        list ($sequence_name, $sequence_field_name) = each($sequence);
                    }
                    $this->last_insert_id_field_name = $sequence_field_name;

                    $sequence_value = $args[$sequence_field_name] = $Db->getSequenceNextValue($sequence_name);
                }
                $affected_rows = $Db->query($sql_query_template, $this->getArgs($args));
                $this->last_insert_id = $sequence_value;
                return $affected_rows;
            },
            $args,
            !empty($sequence)
        );
        return $this->last_insert_id;
    }

    /**
     * Upgrade connection to root privileges, or drop root privileges
     * @param bool $enable
     * @return $this
     */
    public function su($enable = true) {
        /** @var Mysql_Client $Client */
        $Client = $this->getDatabaseClient();
        $Client->su($enable);
        return $this;
    }

    /**
     * Run $fn with root database privileges
     *
     * @param callable $fn
     * @param array $args
     * @return mixed|null
     * @throws \Exception
     */
    public function sudo(callable $fn, array $args = []) {
        /** @var Mysql_Client $Client */
        $Client = $this->getDatabaseClient();
        return $Client->sudo($fn, $args);
    }

    /**
     * @param array $options
     * @return \Carcass\Memcached\TaggedCache
     */
    protected function assembleMct(array $options = []) {
        $mc_key_prefix = Memcached\KeyBuilder::parseString(
            self::UNIT_MEMCACHED_PREFIX_TEMPLATE, [
                'unit_key' => $this->Unit->getKey(),
                'unit_id'  => $this->Unit->getId(),
            ]
        );
        return parent::assembleMct($options + ['prefix' => $mc_key_prefix]);
    }

    /**
     * @return Mysql_Client
     */
    protected function assembleDatabaseClient() {
        return $this->Unit->getDatabaseClient();
    }

    /**
     * @return \Carcass\Memcached\Connection
     */
    protected function assembleMemcachedConnection() {
        return $this->Unit->getMemcachedConnection();
    }

}
