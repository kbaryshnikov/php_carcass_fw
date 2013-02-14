<?php

namespace Carcass\Shard;

use Carcass\Mysql;

class Mysql_Database extends Mysql\Database {

    const
        DEFAULT_SEQUENCE_TABLE_NAME = 'Sequences';

    protected
        $sequence_table_name = self::DEFAULT_SEQUENCE_TABLE_NAME;

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

    public function createSequenceTable() {
        $args = ['table_name' => $this->sequence_table_name];
        $queries = [
            "DROP TABLE IF EXISTS {{ t(table_name) }}",
            "CREATE TABLE {{ t(table_name) }} (
                {{ name(_unit_key) }} integer unsigned NOT NULL,
                name varchar(64) NOT NULL,
                value integer unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY ({{ name(_unit_key) }}, name)
            ) Engine=InnoDB",
        ];
        foreach ($queries as $query) {
            $this->query($query, $args);
        }
        return $this;
    }

}
