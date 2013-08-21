<?php

use \Carcass\Tokumx;
use \Carcass\Connection\Dsn;
use \Carcass\Connection\Manager;

class Tokumx_ConnectionTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        test_tokumx_get_db()->dropCollection('test');
    }

    public function testConnection() {
        $TokuConn = new Tokumx\Connection(Dsn::factory(test_tokumx_get_dsn()));
        $Db = $TokuConn->getDb();
        $result = $Db->execute('"it works";');
        $this->assertEquals('it works', $result['retval']);
        $this->assertEquals(1, $result['ok']);
    }

    public function testTransactionCommit() {
        $Manager = new Manager;
        /** @var Tokumx\Connection $TokuConn */
        $TokuConn = $Manager->getConnection(Dsn::factory(test_tokumx_get_dsn()));
        $Db = $TokuConn->getDb();

        $found = $Db->test->findOne(["test_commit" => 1]);
        $this->assertEmpty($found);

        $Manager->begin();
        $Db->test->insert(["test_commit" => 1]);
        $Manager->commit();

        $found = $Db->test->findOne(["test_commit" => 1]);
        $this->assertNotEmpty($found);
    }

    public function testTransactionRollback() {
        $Manager = new Manager;
        /** @var Tokumx\Connection $TokuConn */
        $TokuConn = $Manager->getConnection(Dsn::factory(test_tokumx_get_dsn()));
        $Db = $TokuConn->getDb();

        $found = $Db->test->findOne(["test_commit" => 1]);
        $this->assertEmpty($found);

        $Manager->begin();
        $Db->test->insert(["test_commit" => 1]);
        $Manager->rollback();

        $found = $Db->test->findOne(["test_commit" => 1]);
        $this->assertEmpty($found);
    }

}
