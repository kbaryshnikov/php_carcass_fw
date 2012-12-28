<?php

use \Carcass\Memcached\Connection;

class Memcached_ConnectionTest extends PHPUnit_Framework_TestCase {

    protected
        $mc_calls = [
            'add'       => true,
            'decrement' => true,
            'delete'    => true,
            'flush'     => false,
            'get'       => false,
            'getStats'  => false,
            'getVersion'=> false,
            'increment' => true,
            'replace'   => true,
            'set'       => true,
            'getServerStatus' => false,
            'getExtendedStats' => false,
        ];

    public function testBasicCalls() {
        foreach ($this->mc_calls as $test_method => $_) {
            $McMock = $this->getMcMock();
            $McMock->expects($this->once())->method($test_method)->with($this->equalTo('a'))->will($this->returnValue('a_value'));
            $McConn = new Connection($this->getTestPool());
            $McConn->setMemcacheInstance($McMock);
            $result = $McConn->$test_method('a');
            $this->assertEquals('a_value', $result);
        }
    }

    public function testPseudoTransactionCommit() {
        $McMock = $this->getMcMock();
        $McMock->expects($this->once())->method('get')->with($this->equalTo('a'))->will($this->returnValue('a_value'));
        $McConn = new Connection($this->getTestPool());
        $McConn->setMemcacheInstance($McMock);
        $McConn->begin();
        $McConn->get('a');
        $McConn->set('a', 1);
        $McMock->expects($this->once())->method('set')->with($this->equalTo('a'), $this->equalTo(1))->will($this->returnValue(true));
        $McConn->commit();
    }

    public function testPseudoTransactionRollback() {
        $McMock = $this->getMcMock();
        $McMock->expects($this->never())->method('set');
        $McConn = new Connection($this->getTestPool());
        $McConn->setMemcacheInstance($McMock);
        $McConn->begin();
        $McConn->set('a', 1);
        $McConn->rollback();
    }

    public function testRequiredCall() {
        $McMock = $this->getMcMock();
        $McMock->expects($this->once())->method('set')->with($this->equalTo('a'), $this->equalTo(1))->will($this->returnValue(true));
        $McMock->expects($this->once())->method('add')->with($this->equalTo('a'), $this->equalTo(1))->will($this->returnValue(false));
        $McConn = new Connection($this->getTestPool());
        $McConn->setMemcacheInstance($McMock);
        $this->assertTrue($McConn->callRequired('set', 'a', '1'));
        $this->setExpectedException('LogicException');
        $McConn->callRequired('add', 'a', '1');
    }

    public function testRequiredCallInsidePseudoTransaction() {
        $McMock = $this->getMcMock();
        $McConn = new Connection($this->getTestPool());
        $McConn->setMemcacheInstance($McMock);
        $McConn->begin();
        $McConn->callRequired('add', 'a', 1);
        $McConn->callRequired('set', 'a', 1);
        $McConn->set('a', 1);
        $McMock->expects($this->once())->method('add')->with($this->equalTo('a'), $this->equalTo(1))->will($this->returnValue(false));
        $McMock->expects($this->never())->method('set');
        $this->setExpectedException('LogicException');
        $McConn->commit();
    }

    protected function getTestPool() {
        return Carcass\Connection\Dsn::factory(['memcached://localhost']);
    }

    protected function getMcMock() {
        return $this->getMockBuilder('Memcache')->disableOriginalConstructor()->getMock();
    }

}
