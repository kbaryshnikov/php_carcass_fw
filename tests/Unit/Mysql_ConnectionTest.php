<?php

use \Carcass\Mysql;
use \Carcass;

class Mysql_ConnectionTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $conn = test_mysql_get_connection();
        $conn->query("drop table if exists test");
        $conn->query("
            create table test (
                id bigint primary key auto_increment,
                s varchar(255)
            ) Engine=InnoDB
        ");
    }

    public function testConnection() {
        $Dsn = new Carcass\Connection\Dsn(test_mysql_get_dsn());
        $Conn = Mysql\Connection::constructWithDsn($Dsn);
        $Conn->executeQuery("SELECT 1 as r");
        $row = $Conn->fetch();
        $this->assertEquals('1', $row['r']);
    }
    
    public function testInsertSelect() {
        $Dsn = new Carcass\Connection\Dsn(test_mysql_get_dsn());
        $Conn = Mysql\Connection::constructWithDsn($Dsn);
        $Conn->executeQuery("INSERT INTO test SET s = '1'");
        $Conn->executeQuery("INSERT INTO test SET s = '2'");
        $Conn->executeQuery("SELECT * FROM test ORDER BY id");
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '1', 's' => '1'], $row);
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '2', 's' => '2'], $row);
    }

    public function testLastInsertId() {
        $Dsn = new Carcass\Connection\Dsn(test_mysql_get_dsn());
        $Conn = Mysql\Connection::constructWithDsn($Dsn);
        $Conn->executeQuery("INSERT INTO test SET s = 'value'");
        $this->assertEquals(1, $Conn->getLastInsertId());
        $Conn->executeQuery("INSERT INTO test SET s = 'value'");
        $this->assertEquals(2, $Conn->getLastInsertId());
    }

    public function testAffectedRows() {
        $Dsn = new Carcass\Connection\Dsn(test_mysql_get_dsn());
        $Conn = Mysql\Connection::constructWithDsn($Dsn);
        $Conn->executeQuery("INSERT INTO test (s) VALUES ('1'), ('2')");
        $this->assertEquals(2, $Conn->getAffectedRows());
        $Conn->executeQuery("UPDATE test SET s='foo' WHERE id = 100500");
        $this->assertEquals(0, $Conn->getAffectedRows());
    }

    public function testTransactions() {
        $Dsn = new Carcass\Connection\Dsn(test_mysql_get_dsn());
        $Conn = Mysql\Connection::constructWithDsn($Dsn);
        $Conn->begin();
        $Conn->executeQuery("INSERT INTO test (s) VALUES ('1')");
        $Conn->commit();
        $Conn->executeQuery("SELECT * FROM test ORDER BY id");
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '1', 's' => '1'], $row);
        $Conn->begin();
        $Conn->executeQuery("INSERT INTO test (s) VALUES ('2')");
        $Conn->rollback();
        $Conn->executeQuery("SELECT * FROM test ORDER BY id");
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '1', 's' => '1'], $row);
    }

    public function testWithConnectionManager() {
        $Manager = new Carcass\Connection\Manager;
        $Conn = $Manager->getConnection(test_mysql_get_dsn());
        $Manager->begin();
        $Conn->executeQuery("INSERT INTO test (s) VALUES ('1')");
        $Manager->commit();
        $Conn->executeQuery("SELECT * FROM test ORDER BY id");
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '1', 's' => '1'], $row);
        $Manager->begin();
        $Conn->executeQuery("INSERT INTO test (s) VALUES ('2')");
        $Manager->rollback();
        $Conn->executeQuery("SELECT * FROM test ORDER BY id");
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '1', 's' => '1'], $row);
        $Conn->doInTransaction(function($Conn) {
            $Conn->executeQuery("UPDATE test SET s = 'new' WHERE id = 1");
        });
        $Conn->executeQuery("SELECT * FROM test ORDER BY id");
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '1', 's' => 'new'], $row);
    }

    public function testQueryTemplate() {
        $Dsn = new Carcass\Connection\Dsn(test_mysql_get_dsn());
        $Conn = Mysql\Connection::constructWithDsn($Dsn);
        $Conn->executeQueryTemplate("INSERT INTO test SET s = {{ s(s) }}", ['s' => 'foo']);
        $Conn->executeQueryTemplate("INSERT INTO test SET s = {{ snul(s) }}", []);
        $Conn->executeQueryTemplate("UPDATE test SET s = {{ s(s) }} WHERE id = {{ id(i) }}", ['i' => 1, 's' => '1']);
        $Conn->executeQueryTemplate("UPDATE test SET s = {{ s(s) }} WHERE s is null", ['s' => 'second\'value']);
        $Conn->executeQueryTemplate("INSERT INTO test SET s = {{ s(s) }}", ['s' => 'third value']);
        $Conn->executeQueryTemplate("SELECT * FROM test ORDER BY id LIMIT {{ lim(limit) }}", ['limit' => 2]);
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '1', 's' => '1'], $row);
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '2', 's' => 'second\'value'], $row);
    }

    public function testMysqlDatabaseWrapper() {
        $Dsn = new Carcass\Connection\Dsn(test_mysql_get_dsn());
        $Conn = Mysql\Connection::constructWithDsn($Dsn);
        $Db = new Mysql\Database($Conn);
        $Db->query("INSERT INTO test (s) VALUES {{ BEGIN values }} ( {{ s }} ) {{ UNLESS _last }} , {{ END }} {{ END }}", ['values' => [
            ['s' => '1'],
            ['s' => '2'],
            ['s' => '3'],
        ]]);

        $expected = [
            ['id'=>'1','s'=>'1'],
            ['id'=>'2','s'=>'2'],
        ];
        $result = $Db->getAll("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id", [ 'id' => 2 ]);
        $this->assertEquals($expected, $result);

        $expected = [
            '1' => ['id'=>'1','s'=>'1'],
            '2' => ['id'=>'2','s'=>'2'],
        ];
        $result = $Db->getAll("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id", [ 'id' => 2 ], [ 'id' => 1 ]);
        $this->assertEquals($expected, $result);

        $expected = [
            '1' => [['id'=>'1','s'=>'1']],
            '2' => [['id'=>'2','s'=>'2']],
        ];
        $result = $Db->getAll("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id", [ 'id' => 2 ], [ 'id' => INF ]);
        $this->assertEquals($expected, $result);

        $expected = [
            '1' => [['1' => ['id'=>'1','s'=>'1']]],
            '2' => [['2' => ['id'=>'2','s'=>'2']]],
        ];
        $result = $Db->getAll("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id", [ 'id' => 2 ], [ 'id' => INF, 's' => 1 ]);
        $this->assertEquals($expected, $result);

        $expected = ['id'=>'1','s'=>'1'];
        $result = $Db->getRow("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id LIMIT {{ lim(limit) }}", [ 'id' => 2, 'limit' => 1 ]);
        $this->assertEquals($expected, $result);

        $expected = ['1', '2', '3'];
        $result = $Db->getCol("SELECT s FROM test ORDER BY id", []);
        $this->assertEquals($expected, $result);

        $expected = ['1', '2', '3'];
        $result = $Db->getCol("SELECT s FROM test ORDER BY id", [], 's');
        $this->assertEquals($expected, $result);

        $expected = ['1' => '1', '2' => '2', '3' => '3'];
        $result = $Db->getCol("SELECT id, s FROM test ORDER BY id", [], 'id', 's');
        $this->assertEquals($expected, $result);

        $result = $Db->getCell("SELECT s FROM test WHERE id = {{ id(id) }}", ['id' => 1]);
        $this->assertEquals('1', $result);
    }

}
