<?php

use \Carcass\Mysql;

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
        $Conn->doInTransaction(function() use ($Conn) {
            $Conn->executeQuery("UPDATE test SET s = 'new' WHERE id = 1");
        });
        $Conn->executeQuery("SELECT * FROM test ORDER BY id");
        $row = $Conn->fetch();
        $this->assertEquals(['id' => '1', 's' => 'new'], $row);
    }

    public function testXA() {
        $Manager = new Carcass\Connection\Manager;
        $Conn = $Manager->getConnection(test_mysql_get_dsn());

        $ConnMock = $this->getMock('\Carcass\Mysql\Connection', [], [ \Carcass\Connection\Dsn::factory('mysql://mock/') ]);
        $ConnMock->expects($this->once())
            ->method('vote')
            ->will($this->returnValue(false));

        /** @noinspection PhpParamsInspection */
        $Manager->addConnection($ConnMock);
            
        $Conn->executeQuery("INSERT INTO test (s) VALUES ('1')");
        $Manager->begin();
        $e = null;
        try {
            $Conn->executeQuery("INSERT INTO test (s) VALUES ('2')");
            $ConnMock->executeQuery('foo');
            $Manager->commit();
        } catch (Exception $e) {
            $Manager->rollback();
        }
        $this->assertInstanceOf('\Carcass\Connection\ManagerXaVotedNoException', $e);

        $Conn->executeQuery("SELECT count(*) c FROM test");
        $this->assertEquals('1', $Conn->fetch()['c']);
    }

}
