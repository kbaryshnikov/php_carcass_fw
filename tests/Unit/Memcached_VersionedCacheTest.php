<?php

use \Carcass\Memcached;

class Memcached_VersionedCacheTest extends PHPUnit_Framework_TestCase {

    public function testSetNew() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('callRaw')->with($this->equalTo('increment'), $this->equalTo('ns:version'))
            ->will($this->returnValue(false));

        $ConnMock->expects($this->once())
            ->method('callRawRequired')->with($this->equalTo('set'), $this->equalTo('ns:version'), $this->anything())
            ->will($this->returnValue(true));

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('set'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals('ns::key_test', $args[0]);
                $this->assertEquals(['value', $VC->getLastVersion()], $args[1]);
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $VC->set($Key, ['s' => 'test'], 'value');
    }

    public function testSetExisting() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('callRaw')->with($this->equalTo('increment'), $this->equalTo('ns:version'))
            ->will($this->returnValue(123));

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('set'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals('ns::key_test', $args[0]);
                $this->assertEquals(['value', 123], $args[1]);
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $VC->set($Key, ['s' => 'test'], 'value');
    }

    public function testSetMulti() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('callRaw')->with($this->equalTo('increment'), $this->equalTo('ns:version'))
            ->will($this->returnValue(123));

        $ConnMock->expects($this->exactly(2))
            ->method('__call')->with($this->equalTo('set'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                static $idx = 1;
                $this->assertEquals("ns::key${idx}_test$idx", $args[0]);
                $this->assertEquals(["value$idx", 123], $args[1]);
                $idx++;
            }));

        $Key1 = Memcached\Key::create('key1_{{ s(s) }}');
        $Key2 = Memcached\Key::create('key2_{{ s(s) }}');
        $VC->setMulti([
            [$Key1, ['s' => 'test1'], 'value1'],
            [$Key2, ['s' => 'test2'], 'value2'],
        ]);
    }

    public function testGetNotExpired() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals(['ns::key_test', 'ns:version'], $args[0]);
                return [
                    'ns::key_test' => [ 'value', 123 ],
                    'ns:version' => 123
                ];
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $result = $VC->get($Key, ['s' => 'test']);
        $this->assertEquals('value', $result);
    }

    public function testGetExpiredVersionNumber() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals(['ns::key_test', 'ns:version'], $args[0]);
                return [
                    'ns::key_test' => [ 'value', 122 ],
                    'ns:version' => 123
                ];
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $result = $VC->get($Key, ['s' => 'test']);
        $this->assertFalse($result);
    }

    public function testGetExpiredVersionKey() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals(['ns::key_test', 'ns:version'], $args[0]);
                return [
                    'ns::key_test' => [ 'value', 122 ],
                ];
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $result = $VC->get($Key, ['s' => 'test']);
        $this->assertFalse($result);
    }

    public function testGetMultiNotExpired() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals(['ns::key_test1', 'ns::key_test2', 'ns::key2_test3', 'ns:version'], $args[0]);
                return [
                    'ns::key_test1' => [ 'value1', 123 ],
                    'ns::key_test2' => [ 'value2', 123 ],
                    'ns::key2_test3' => [ 'value3', 123 ],
                    'ns:version' => 123
                ];
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $Key2 = Memcached\Key::create('key2_{{ s(s) }}');
        $result = $VC->getMulti([
            [$Key, ['s' => 'test1']],
            [$Key, ['s' => 'test2']],
            [$Key2, ['s' => 'test3']],
        ]);
        $this->assertEquals([
            'key_test1' => 'value1',
            'key_test2' => 'value2',
            'key2_test3' => 'value3',
        ], $result);
    }

    public function testGetMultiExpiredVersionNumber() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals(['ns::key_test1', 'ns::key_test2', 'ns::key2_test3', 'ns:version'], $args[0]);
                return [
                    'ns::key_test1' => [ 'value1', 123 ],
                    'ns::key_test2' => [ 'value2', 123 ],
                    'ns::key2_test3' => [ 'value3', 123 ],
                    'ns:version' => 1
                ];
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $Key2 = Memcached\Key::create('key2_{{ s(s) }}');
        $result = $VC->getMulti([
            [$Key, ['s' => 'test1']],
            [$Key, ['s' => 'test2']],
            [$Key2, ['s' => 'test3']],
        ]);
        $this->assertEmpty($result);
    }

    public function testGetMultiPartiallyExpred() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals(['ns::key_test1', 'ns::key_test2', 'ns::key2_test3', 'ns:version'], $args[0]);
                return [
                    'ns::key_test1' => [ 'value1', 123 ],
                    'ns::key_test2' => [ 'value2', 123 ],
                    'ns::key2_test3' => [ 'value3', 1 ],
                    'ns:version' => 123
                ];
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $Key2 = Memcached\Key::create('key2_{{ s(s) }}');
        $result = $VC->getMulti([
            [$Key, ['s' => 'test1']],
            [$Key, ['s' => 'test2']],
            [$Key2, ['s' => 'test3']],
        ]);
        $this->assertEquals([
            'key_test1' => 'value1',
            'key_test2' => 'value2',
        ], $result);
    }

    public function testGetMultiExpiredVersionKey() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnCallback(function($method, $args) use($VC) {
                $this->assertEquals(['ns::key_test1', 'ns::key_test2', 'ns::key2_test3', 'ns:version'], $args[0]);
                return [
                    'ns::key_test1' => [ 'value1', 123 ],
                    'ns::key_test2' => [ 'value2', 123 ],
                    'ns::key2_test3' => [ 'value3', 123 ],
                ];
            }));

        $Key = Memcached\Key::create('key_{{ s(s) }}');
        $Key2 = Memcached\Key::create('key2_{{ s(s) }}');
        $result = $VC->getMulti([
            [$Key, ['s' => 'test1']],
            [$Key, ['s' => 'test2']],
            [$Key2, ['s' => 'test3']],
        ]);
        $this->assertEmpty($result);
    }

    public function testFlush() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);
        $VC->setNamespace('ns');

        $ConnMock->expects($this->once())
            ->method('callRaw')->with($this->equalTo('delete'), $this->equalTo('ns:version'));

        $VC->flush();
    }

    public function testFlushNamespace() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $VC = new Memcached\VersionedCache($ConnMock);

        $ConnMock->expects($this->exactly(2))
            ->method('callRaw')->with($this->equalTo('delete'), $this->equalTo('ns2:version'));

        $VC->flushNamespace('ns2');
        $VC->flushNamespaceByKey(Memcached\Key::create('ns{{ id(i) }}'), ['i' => 2]);
    }

    protected function getConnectionMock() {
        return $this->getMockBuilder('\Carcass\Memcached\Connection')->disableOriginalConstructor()->getMock();
    }

}
