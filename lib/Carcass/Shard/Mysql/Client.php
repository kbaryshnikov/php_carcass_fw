<?php

namespace Carcass\Shard;

use Carcass\Mysql;
use Carcass\Application\Injector;

class Mysql_ShardClient extends Mysql\Client {

    const
        DEFAULT_SEQUENCE_TABLE_NAME = 'Seq';

    protected
        $Unit = null,
        $sequence_table_name = self::DEFAULT_SEQUENCE_TABLE_NAME;

    public function __construct(UnitInterface $Unit, QueryParser $QueryParser = null) {
        $this->Unit = $Unit;
        parent::__construct(
            Injector::getConnectionManager()->getConnection($Unit->getShard()->getDsn()),
            $QueryParser
        );
    }

    public function getUnit() {
        if (null === $this->Unit) {
            throw new \LogicException('Shard unit is undefined');
        }
        return $this->Unit;
    }

    public function setSequenceTableName($sequence_table_name) {
        $this->sequence_table_name = $sequence_table_name;
        return $this;
    }

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

    protected function assembleDefaultQueryParser() {
        return new Mysql_QueryParser($this);
    }

}
