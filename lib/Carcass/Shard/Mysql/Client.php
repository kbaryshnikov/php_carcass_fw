<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Shard;

use Carcass\Mysql;
use Carcass\Application\Injector;

/**
 * Shard mysql client
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
     * @param UnitInterface $Unit
     * @param Mysql_QueryParser $QueryParser
     */
    public function __construct(UnitInterface $Unit, Mysql_QueryParser $QueryParser = null) {
        $this->Unit = $Unit;
        /** @noinspection PhpParamsInspection */
        parent::__construct(
            Injector::getConnectionManager()->getConnection($Unit->getShard()->getDsn()),
            $QueryParser
        );
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
        return $this->doInTransaction(function() use ($sequence_name) {
            $this->query(
                "INSERT INTO {{ t(table_name) }}
                {{ set() }}
                    name = {{ s(sequence_name) }},
                    value = {{ i(initial_value) }}
                ON DUPLICATE KEY UPDATE
                    value = value + 1
                ",
                [
                    'table_name' => $this->sequence_table_name,
                    'sequence_name' => $sequence_name,
                    'initial_value' => $initial_value,
                ]
            );
            return $this->getSequenceCurrentValue($sequence_name);
        });
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
                'table_name' => $this->sequence_table_name,
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
                'table_name' => $this->sequence_table_name,
                'sequence_name' => $sequence_name,
                'value' => $value,
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
