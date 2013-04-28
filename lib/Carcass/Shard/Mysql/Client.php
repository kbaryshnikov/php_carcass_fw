<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Mysql;
use Carcass\Application\DI;

/**
 * Shard mysql client
 *
 * @method int getAffectedRows()
 * @method int getLastInsertId()
 * @method string escapeString()
 * @method string getDsn()
 * @method mixed doInTransaction()
 *
 * @package Carcass\Shard
 */
class Mysql_Client extends Mysql\Client {

    const DEFAULT_SEQUENCE_TABLE_NAME = 'Seq';

    /**
     * @var UnitInterface
     */
    protected $Unit;
    /**
     * @var string
     */
    protected $sequence_table_name = self::DEFAULT_SEQUENCE_TABLE_NAME;

    /**
     * @var bool
     */
    protected $is_su = false;

    /**
     * @param UnitInterface $Unit
     * @param Mysql_QueryParser $QueryParser
     */
    public function __construct(UnitInterface $Unit, Mysql_QueryParser $QueryParser = null) {
        $this->Unit = $Unit;

        /** @var $Connection Mysql\Connection */
        $Connection = DI::getConnectionManager()->getConnection($Unit->getShard()->getDsn());
        parent::__construct($Connection, $QueryParser);
    }

    /**
     * Upgrade connection to root privileges, or drop root privileges
     * @param bool $enable
     * @return $this
     */
    public function su($enable = true) {
        if ($enable != $this->is_su) {
            $this->is_su = $enable;
            $Dsn = $this->Unit->getShard()->getDsn($this->is_su);
            /** @var \Carcass\Mysql\Connection $Connection */
            $Connection = DI::getConnectionManager()->getConnection($Dsn);
            $this->setConnection($Connection);
        }
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
        $result = null;
        try {
            $this->su(true);
            $result = call_user_func_array($fn, $args);
        } catch (\Exception $e) {
            // pass
        }
        // finally:
        $this->su(false);
        if (isset($e)) {
            throw $e;
        }
        return $result;
    }

    /**
     * @return UnitInterface
     */
    public function getUnit() {
        return $this->Unit;
    }

    /**
     * @param $sequence_table_name
     * @return $this
     */
    public function setSequenceTableName($sequence_table_name) {
        $this->sequence_table_name = $sequence_table_name;
        return $this;
    }

    /**
     * @param $sequence_name
     * @param int $initial_value
     * @return mixed
     */
    public function getSequenceNextValue($sequence_name, $initial_value = 1) {
        return $this->doInTransaction(
            function () use ($sequence_name, $initial_value) {
                $this->query(
                    "INSERT INTO {{ t(table_name) }}
                    {{ set() }}
                        name = {{ s(sequence_name) }},
                        value = {{ i(initial_value) }}
                    ON DUPLICATE KEY UPDATE
                        value = value + 1
                    ",
                    [
                        'table_name'    => $this->sequence_table_name,
                        'sequence_name' => $sequence_name,
                        'initial_value' => $initial_value,
                    ]
                );
                return $this->getSequenceCurrentValue($sequence_name);
            }
        );
    }

    /**
     * @param $sequence_name
     * @return bool|null|string
     */
    public function getSequenceCurrentValue($sequence_name) {
        return $this->getCell(
            "SELECT
                value
            FROM
                {{ t(table_name) }}
            {{ where() }}
                name = {{ s(sequence_name) }}",
            [
                'table_name'    => $this->sequence_table_name,
                'sequence_name' => $sequence_name,
            ]
        );
    }

    /**
     * @param $sequence_name
     * @param $value
     * @return $this
     */
    public function setSequenceValue($sequence_name, $value) {
        $this->query(
            "INSERT INTO {{ t(table_name) }}
            {{ set() }}
                name = {{ s(sequence_name) }},
                value = {{ i(value) }}
            ON DUPLICATE KEY UPDATE
                value = {{ i(value) }}
            ",
            [
                'table_name'    => $this->sequence_table_name,
                'sequence_name' => $sequence_name,
                'value'         => $value,
            ]
        );
        return $this;
    }

    /**
     * @param bool $drop_existing
     * @return $this
     */
    public function createSequenceTable($drop_existing = false) {
        $args = ['table_name' => $this->sequence_table_name];
        if ($drop_existing) {
            $queries[] = "DROP TABLE IF EXISTS {{ t(table_name) }}";
        }
        $queries[] =
            "CREATE TABLE {{ t(table_name) }} (
                {{ name(_unit_key) }} integer unsigned NOT NULL,
                name varchar(64) NOT NULL,
                value integer unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY ({{ name(_unit_key) }}, name)
            ) Engine=InnoDB";
        foreach ($queries as $query) {
            $this->query($query, $args);
        }
        return $this;
    }

    /**
     * @return Mysql_QueryParser
     */
    protected function assembleDefaultQueryParser() {
        return new Mysql_QueryParser($this);
    }

}
