<?php

use \Carcass\Connection;
use \Carcass\Corelib;

class Connection_ManagerTest extends PHPUnit_Framework_TestCase {
    
    public function testGetConnectionByDsnString() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['test' => 'TestConnection']);
        $TC = $CM->getConnection('test://localhost/');
        $this->assertInstanceOf('TestConnection', $TC);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals('test://localhost/', (string)$TC->Dsn);
    }

    public function testGetConnectionByDsn() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['test' => 'TestConnection']);
        $TC = $CM->getConnectionByDsn(Connection\Dsn::factory('test://localhost/'));
        $this->assertInstanceOf('TestConnection', $TC);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals('test://localhost/', (string)$TC->Dsn);
    }

    public function testConnectionWithSameDsnIsReused() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['test' => 'TestConnection']);
        $this->assertSame($CM->getConnection('test://localhost/'), $CM->getConnection('test://localhost'));
        $this->assertSame($CM->getConnection('test://localhost/'), $CM->getConnectionByDsn(Connection\Dsn::factory('test://localhost')));
    }

    public function testGetConnectionByPool() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['testpool' => 'TestPoolConnection']);
        $TPC = $CM->getConnection(['testpool://localhost/']);
        $this->assertInstanceOf('TestPoolConnection', $TPC);
    }

    public function testPoolConnectionWithSameDsnIsReused() { 
        $CM = new Connection\Manager;
        $CM->replaceTypes(['testpool' => 'TestPoolConnection']);
        $this->assertSame($CM->getConnection(['testpool://localhost/']), $CM->getConnection(['testpool://localhost']));
        $this->assertSame($CM->getConnection(['testpool://localhost/']), $CM->getConnectionByDsn(Connection\Dsn::factory(['testpool://localhost'])));
    }

    public function testTransactions() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['test' => 'TestTransactionalConnection']);
        /** @var TransactionalConnectionTraitUser $TTC */
        $TTC = $CM->getConnection('test://localhost');
        /** @var TransactionalConnectionTraitUser $TTC2 */
        $TTC2 = $CM->getConnection('test://remotehost');
        $this->assertInstanceOf('TestTransactionalConnection', $TTC);
        $this->assertInstanceOf('TestTransactionalConnection', $TTC2);
        $this->assertNotSame($TTC, $TTC2);
        $CM->begin();
        $this->assertEquals('begin', $TTC->transaction);
        $this->assertEquals('begin', $TTC2->transaction);
        $CM->commit();
        $this->assertEquals('commit', $TTC->transaction);
        $this->assertEquals('commit', $TTC2->transaction);
        $CM->rollback();
        $this->assertEquals('rollback', $TTC->transaction);
        $this->assertEquals('rollback', $TTC2->transaction);
    }

    public function testTransactionRollbackException() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['test' => 'TestTransactionalConnection']);
        /** @var TransactionalConnectionTraitUser $TTC */
        $TTC = $CM->getConnection('test://localhost');
        $result = $CM->doInTransaction(function() {
            throw new Connection\TransactionRollbackException(123);
        });
        $this->assertEquals(123, $result);
        $this->assertEquals('rollback', $TTC->transaction);
    }

    public function testTransactionCallsFilterSource() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['test' => 'TestTransactionalConnection']);
        /** @var TransactionalConnectionTraitUser $TTC */
        $TTC = $CM->getConnection('test://localhost');
        /** @var TransactionalConnectionTraitUser $TTC2 */
        $TTC2 = $CM->getConnection('test://remotehost');
        /** @noinspection PhpParamsInspection */
        $CM->begin($TTC2);
        $this->assertEquals('begin', $TTC->transaction);
        $this->assertNull($TTC2->transaction);
        /** @noinspection PhpParamsInspection */
        $CM->commit($TTC2);
        $this->assertEquals('commit', $TTC->transaction);
        $this->assertNull($TTC2->transaction);
        /** @noinspection PhpParamsInspection */
        $CM->rollback($TTC2);
        $this->assertEquals('rollback', $TTC->transaction);
        $this->assertNull($TTC2->transaction);
    }

    public function testConnectionIteration() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['test' => 'TestConnection']);
        $expect[] = $CM->getConnection('test://localhost/');
        $expect[] = $CM->getConnection('test://remotehost/');
        $got = [];
        $CM->forEachConnection(function($Connection) use (&$got) {
            $this->assertInstanceOf('TestConnection', $Connection);
            $got[] = $Connection;
        });
        $this->assertSame($expect, $got);
    }

    public function testTransactionalConnectionsIteration() {
        $CM = new Connection\Manager;
        $CM->replaceTypes(['test' => 'TestConnection', 'testtrans' => 'TestTransactionalConnection']);
        $CM->getConnection('test://localhost');
        $expect[] = $CM->getConnection('testtrans://localhost');
        $got = [];
        $CM->forEachTransactionalConnection(function($Connection) use (&$got) {
            $this->assertInstanceOf('TestTransactionalConnection', $Connection);
            $got[] = $Connection;
        });
        $this->assertSame($expect, $got);
    }

}

class TestConnection implements Connection\ConnectionInterface {

    public static function constructWithDsn(Connection\Dsn $Dsn) {
        $self = new static;
        /** @noinspection PhpUndefinedFieldInspection */
        $self->Dsn = $Dsn;
        return $self;
    }

    public function getDsn(){}

}

class TestPoolConnection extends TestConnection implements Connection\PoolConnectionInterface {

    public static function constructWithPool(Connection\DsnPool $DsnPool) {
        $self = new static;
        /** @noinspection PhpUndefinedFieldInspection */
        $self->DsnPool = $DsnPool;
        return $self;
    }

}

/**
 * @property \Carcass\Connection\Manager Manager
 */
class TestTransactionalConnection extends TestConnection implements Connection\TransactionalConnectionInterface {

    public $transaction = null;

    public function setManager(Connection\Manager $Manager) {
        $this->Manager = $Manager;
    }

    public function getTransactionId() {
        return null;
    }
    
    public function begin($local = false) {
        if (!$local) throw new Exception("Call must be local");
        $this->transaction = 'begin';
    }
    
    public function commit($local = false) {
        if (!$local) throw new Exception("Call must be local");
        $this->transaction = 'commit';
    }
    
    public function rollback($local = false) {
        if (!$local) throw new Exception("Call must be local");
        $this->transaction = 'rollback';
    }

}
