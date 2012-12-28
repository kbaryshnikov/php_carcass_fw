<?php

use \Carcass\Memcached;

class Memcached_TaggedCacheTest extends PHPUnit_Framework_TestCase {

    public function testGetOne() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important',
            'unimportant'
        ]);
        $ConnMock->expects($this->exactly(2))
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['x', '&important']]))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important' => '1.2.3' ],
                ],
                '&important' => '1.2.3',
            ]));
        $this->assertEquals('x_value', $TC->get('x'));
        $this->assertEquals('x_value', $TC->getKey(Memcached\Key::create('{{ s(s) }}'), ['s'=>'x']));
    }

    public function testGetMulti() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important',
            'unimportant'
        ]);
        $ConnMock->expects($this->exactly(2))
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['x', 'y', '&important']]))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important' => '1.2.3' ],
                ],
                'y' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'y_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important' => '1.2.3' ],
                ],
                '&important' => '1.2.3',
            ]));
        $this->assertEquals(['x' => 'x_value', 'y' => 'y_value'], $TC->getMulti(['x', 'y']));
        $Key = Memcached\Key::create('{{ s(s) }}');
        $this->assertEquals(['x' => 'x_value', 'y' => 'y_value'], $TC->getKeys([
            [$Key, ['s'=>'x']],
            [$Key, ['s'=>'y']]
        ]));
    }

    public function testTagExpiredGetOne() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important',
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['x', '&important']]))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important' => '1.2.0' ],
                ],
                '&important' => '1.2.3',
            ]));
        $result = $TC->get('x');
        $this->assertFalse($result);
    }

    public function testTagKeyExpiredGetOne() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important',
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['x', '&important']]))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important' => '1.2.0' ],
                ]
            ]));
        $result = $TC->get('x');
        $this->assertFalse($result);
    }

    public function testMultipleImportantTagsGetOne() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, [
            [ 'important1', 'important2' ],
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['x', '&important1', '&important2']]))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important1' => '1', 'important2' => '2' ],
                ],
                '&important1' => '1',
                '&important2' => '2',
            ]));
        $result = $TC->get('x');
        $this->assertEquals('x_value', $result);
    }

    public function testOneOfImportantTagsExpiredGetOne() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, [
            [ 'important1', 'important2' ],
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['x', '&important1', '&important2']]))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important1' => '1', 'important2' => '2' ],
                ],
                '&important1' => '1',
                '&important2' => '0',
            ]));
        $result = $TC->get('x');
        $this->assertFalse($result);
    }

    public function testGetMultiExpiredOneValue() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important',
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['x', 'y', '&important']]))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important' => '1.2.3' ],
                ],
                'y' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'y_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important' => '0' ],
                ],
                '&important' => '1.2.3',
            ]));
        $result = $TC->getMulti(['x', 'y']);
        $this->assertEquals(['x' => 'x_value'], $result);
    }

    public function testGetMultiWithManyImportantKeysExpired() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, [
            [ 'important1', 'important2' ],
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['x', 'y', '&important1', '&important2']]))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important2' => '1.2.3' ],
                ],
                'y' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'y_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ 'important1' => '0' ],
                ],
                '&important1' => '99'
            ]));
        $result = $TC->getMulti(['x', 'y']);
        $this->assertEquals([], $result);
    }

    public function testSetOne() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, ['important', 'unimportant']);
        $ConnMock->expects($this->exactly(3))
            ->method('__call')->with($this->equalTo('set'));
        $TC->set('a', 1);

        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, ['important', 'unimportant']);
        $ConnMock->expects($this->exactly(3))
            ->method('__call')->with($this->equalTo('set'));
        $TC->setKey(Memcached\Key::create('{{ s(s) }}'), ['s' => 'a'], 1);
    }

    public function testSetMulti() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, ['important', 'unimportant']);
        $ConnMock->expects($this->exactly(4))
            ->method('__call')->with($this->equalTo('set'));
        $TC->setMulti(['a'=>1,'b'=>2]);

        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, ['important', 'unimportant']);
        $ConnMock->expects($this->exactly(4))
            ->method('__call')->with($this->equalTo('set'));
        $Key = Memcached\Key::create('{{ s(s) }}');
        $TC->setKeys([
            [$Key, ['s'=>'a']],
            [$Key, ['s'=>'b']],
        ]);
    }

    public function testFlush() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, ['important', 'unimportant']);
        $ConnMock->expects($this->exactly(2))->method('__call')->with($this->equalTo('delete'));
        $TC->flush();
    }

    public function testFlushPartial() {
        $ConnMock = $this->getConnectionMock();
        $TC = new Memcached\TaggedCache($ConnMock, ['important', 'unimportant']);
        $ConnMock->expects($this->exactly(1))->method('__call')->with($this->equalTo('delete'));
        $TC->flush(['important']);
    }

    protected function getConnectionMock() {
        return $this->getMockBuilder('\Carcass\Memcached\Connection')->disableOriginalConstructor()->getMock();
    }
}
