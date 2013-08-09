<?php

use Carcass\Memcached;

class Bug2_MemcachedConnection_KeyBuilder_BlitzDoubleLoad_Test extends PHPUnit_Framework_TestCase {

    public function testMemcachedConnectionCreatesNewKeyBuilderWhenKeyChanges() {
        $DsnPoolMock = $this->getMock('\Carcass\Connection\DsnPool');
        $DsnPoolMock->expects($this->any())->method('getType')->will($this->returnValue('memcached'));
        /** @var \Carcass\Connection\DsnPool $DsnPoolMock */
        $MemcachedConnection = new Memcached\Connection($DsnPoolMock);
        $this->assertEquals('a1', $MemcachedConnection->buildKey('a{{ i(i) }}', ['i' => 1]));
        $this->assertEquals('b2', $MemcachedConnection->buildKey('b{{ i(i) }}', ['i' => 2]));
    }

}