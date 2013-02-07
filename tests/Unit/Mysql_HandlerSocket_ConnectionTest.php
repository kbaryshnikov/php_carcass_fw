<?php

use \Carcass\Mysql;
use \Carcass;

class Mysql_HandlerSocket_ConnectionTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        $conn = test_mysql_get_connection();
        $conn->query("drop table if exists test");
        $conn->query("
            create table test (
                id bigint primary key auto_increment,
                i integer,
                s varchar(255),
                KEY idi (id, i)
            ) Engine=InnoDB
        ");
        $conn->query("INSERT INTO test (i, s) VALUES (1, 'v1'), (2, 'v2')");
    }

    public function testGetEq() {
        $Dsn = new Carcass\Connection\Dsn(test_hs_get_dsn());
        $Conn = Mysql\HandlerSocket_Connection::constructWithDsn($Dsn);
        $Index = $Conn->openIndex('test', 'PRIMARY', ['id']);
        $result = $Index->find('=', ['id' => 1]);
        $this->assertEquals('1', $result[0]['id']);
    }

    public function testGetEqOne() {
        $Dsn = new Carcass\Connection\Dsn(test_hs_get_dsn());
        $Conn = Mysql\HandlerSocket_Connection::constructWithDsn($Dsn);
        $Index = $Conn->openIndex('test', 'PRIMARY', ['id']);
        $result = $Index->find('==', ['id' => 1]);
        $this->assertEquals('1', $result['id']);
    }

    public function testGetMany() {
        $Dsn = new Carcass\Connection\Dsn(test_hs_get_dsn());
        $Conn = Mysql\HandlerSocket_Connection::constructWithDsn($Dsn);
        $Index = $Conn->openIndex('test', 'PRIMARY', ['id', 'i', 's']);
        $result = $Index->find('>=', ['id' => 1], ['limit'=>2]);
        $this->assertEquals(2, count($result));
        $this->assertEquals('1', $result[0]['id']);
        $this->assertEquals('1', $result[0]['i']);
        $this->assertEquals('v1', $result[0]['s']);
        $this->assertEquals('2', $result[1]['id']);
        $this->assertEquals('2', $result[1]['i']);
        $this->assertEquals('v2', $result[1]['s']);
    }

    public function testManyConditions() {
        $Dsn = new Carcass\Connection\Dsn(test_hs_get_dsn());
        $Conn = Mysql\HandlerSocket_Connection::constructWithDsn($Dsn);
        $Index = $Conn->openIndex('test', 'idi', ['id', 'i']);
        $result = $Index->find('=', ['id' => 1, 'i' => 1], ['limit'=>2]);
        $this->assertEquals(1, count($result));
        $this->assertEquals('1', $result[0]['id']);

        $result = $Index->find('=', ['id' => 2, 'i' => 1], ['limit'=>2]);
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

}