<?php

use \Carcass\Connection;

class TransactionalConnectionTraitUser implements Connection\TransactionalConnectionInterface {
    use Connection\TransactionalConnectionTrait;

    public $transaction = null;

    public function query() {
        $this->triggerScheduledTransaction();
    }
    public function getTransactionStatus() {
        return $this->transaction_status;
    }
    public function getTransactionCounter() {
        return $this->transaction_counter;
    }
    protected function beginTransaction() {
        $this->transaction = 'begin';
    }
    protected function commitTransaction() {
        $this->transaction = 'commit';
    }
    protected function rollbackTransaction() {
        $this->transaction = 'rollback';
    }
}

class Connection_TransactionalConnectionTraitTest extends PHPUnit_Framework_TestCase {

    public function testUnmanagedCommit() {
        $T = new TransactionalConnectionTraitUser;

        $this->assertEquals(0, $T->getTransactionStatus());
        $this->assertEquals(0, $T->getTransactionCounter());

        $T->begin();
        $this->assertNull($T->transaction);
        $this->assertEquals(1, $T->getTransactionStatus());
        $this->assertEquals(1, $T->getTransactionCounter());

        $T->query();
        $this->assertEquals('begin', $T->transaction);
        $this->assertEquals(2, $T->getTransactionStatus());
        $this->assertEquals(1, $T->getTransactionCounter());

        $T->commit();
        $this->assertEquals('commit', $T->transaction);
        $this->assertEquals(0, $T->getTransactionStatus());

        $T = new TransactionalConnectionTraitUser;
        $T->begin();
        $this->assertNull($T->transaction);
        $T->commit();
        $this->assertNull($T->transaction);
        $this->assertEquals(0, $T->getTransactionStatus());
        $this->assertEquals(0, $T->getTransactionCounter());
    }

    public function testUnmanagedRollback() {
        $T = new TransactionalConnectionTraitUser;
        $T->begin();
        $T->query();

        $T->rollback();
        $this->assertEquals('rollback', $T->transaction);
        $this->assertEquals(0, $T->getTransactionStatus());
        $this->assertEquals(0, $T->getTransactionCounter());

        $T = new TransactionalConnectionTraitUser;
        $T->begin();

        $T->rollback();

        $this->assertNull($T->transaction);
        $this->assertEquals(0, $T->getTransactionStatus());
        $this->assertEquals(0, $T->getTransactionCounter());
    }

    public function testNestedCommit() {
        $T = new TransactionalConnectionTraitUser;

        $T->begin();
        $this->assertNull($T->transaction);
        $this->assertEquals(1, $T->getTransactionStatus());
        $this->assertEquals(1, $T->getTransactionCounter());

        $T->begin();
        $this->assertNull($T->transaction);
        $this->assertEquals(1, $T->getTransactionStatus());
        $this->assertEquals(2, $T->getTransactionCounter());

        $T->query();
        $this->assertEquals('begin', $T->transaction);
        $this->assertEquals(2, $T->getTransactionStatus());
        $this->assertEquals(2, $T->getTransactionCounter());

        $T->commit();
        $this->assertEquals('begin', $T->transaction);
        $this->assertEquals(2, $T->getTransactionStatus());
        $this->assertEquals(1, $T->getTransactionCounter());

        $T->commit();
        $this->assertEquals('commit', $T->transaction);
        $this->assertEquals(0, $T->getTransactionStatus());
        $this->assertEquals(0, $T->getTransactionCounter());
    }

    public function testNestedRollback() {
        $T = new TransactionalConnectionTraitUser;

        $T->begin();
        $this->assertNull($T->transaction);
        $this->assertEquals(1, $T->getTransactionStatus());
        $this->assertEquals(1, $T->getTransactionCounter());

        $T->begin();
        $this->assertNull($T->transaction);
        $this->assertEquals(1, $T->getTransactionStatus());
        $this->assertEquals(2, $T->getTransactionCounter());

        $T->query();
        $this->assertEquals('begin', $T->transaction);
        $this->assertEquals(2, $T->getTransactionStatus());
        $this->assertEquals(2, $T->getTransactionCounter());

        $T->rollback();
        $this->assertEquals('rollback', $T->transaction);
        $this->assertEquals(0, $T->getTransactionStatus());
        $this->assertEquals(0, $T->getTransactionCounter());
    }

    public function testManagedCommit() {
        $T = new TransactionalConnectionTraitUser;
        $ManagerMock = $this->getMock('\Carcass\Connection\Manager');
        $T->setManager($ManagerMock);

        $ManagerMock->expects($this->once())->method('begin')->with($this->equalTo($T));
        $T->begin();

        $ManagerMock->expects($this->never())->method('begin');
        $T->begin(true);

        $this->assertNull($T->transaction);
        $T->query();
        $this->assertEquals('begin', $T->transaction);
        $T->commit(true);

        $ManagerMock->expects($this->once())->method('commit')->with($this->equalTo($T));
        $T->commit();
        $this->assertEquals('commit', $T->transaction);
    }

    public function testManagedRollback() {
        $T = new TransactionalConnectionTraitUser;
        $ManagerMock = $this->getMock('\Carcass\Connection\Manager');
        $T->setManager($ManagerMock);
        $T->begin();
        $T->query();

        $ManagerMock->expects($this->once())->method('rollback')->with($this->equalTo($T));
        $T->rollback();

        $ManagerMock->expects($this->never())->method('rollback');
        $T->begin(true);
        $T->query();
        $T->rollback(true);

        $this->assertEquals('rollback', $T->transaction);
    }


}
