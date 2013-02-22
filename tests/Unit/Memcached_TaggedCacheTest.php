<?php

use \Carcass\Memcached;

class Memcached_TaggedCacheTest extends PHPUnit_Framework_TestCase {

    public function testGetOne() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important_{{ i(id) }}',
            'unimportant_{{ i(id) }}'
        ]);

        $ConnMock->expects($this->any())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['&important_1', 'x_1']]))
            ->will($this->returnValue([
                'x_1' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => '1.2.3' ],
                ],
                '&important_1' => '1.2.3',
            ]));

        $this->assertEquals('x_value', $TC->get('x_{{ i(id) }}', ['id' => '1']));

        $this->assertEquals('x_value', $TC->getKey(Memcached\Key::create('x_{{ i(id) }}'), ['id'=>1]));
    }

    public function testGetMulti() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important_{{ i(id) }}',
            'unimportant_{{ i(id) }}'
        ]);
        $ConnMock->expects($this->any())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['&important_1', 'x_1', 'y_1']]))
            ->will($this->returnValue([
                'x_1' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => '1.2.3' ],
                ],
                'y_1' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'y_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => '1.2.3' ],
                ],
                '&important_1' => '1.2.3',
            ]));

        $this->assertEquals(
            ['x_{{ i(id) }}' => 'x_value', 'y_{{ i(id) }}' => 'y_value'],
            $TC->getMulti(['x_{{ i(id) }}', 'y_{{ i(id) }}'], ['id' => 1])
        );

        $KeyX = Memcached\Key::create('x_{{ i(id) }}');
        $KeyY = Memcached\Key::create('y_{{ i(id) }}');

        $this->assertEquals(
            [0 => 'x_value', 1 => 'y_value'],
            $TC->getKeys([$KeyX, $KeyY], ['id' => 1])
        );
    }

    public function testTagExpiredGetOne() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important',
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'), $this->equalTo([['&important', 'x']]))
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
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important',
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important' => '1.2.0' ],
                ]
            ]));
        $result = $TC->get('x');
        $this->assertFalse($result);
    }

    public function testMultipleImportantTagsGetOne() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            [ 'important1', 'important2' ],
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important1' => '1', '&important2' => '2' ],
                ],
                '&important1' => '1',
                '&important2' => '2',
            ]));
        $result = $TC->get('x');
        $this->assertEquals('x_value', $result);
    }

    public function _testOneOfImportantTagsExpiredGetOne() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            [ 'important1', 'important2_{{ i(id) }}' ],
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important1' => '1', '&important2_1' => '2' ],
                ],
                '&important1' => '1',
                '&important2_1' => '0',
            ]));
        $result = $TC->get('x', ['id'=>1]);
        $this->assertFalse($result);
    }

    public function testGetMultiExpiredOneValue() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            'important',
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important' => '1.2.3' ],
                ],
                'y' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'y_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important' => '0' ],
                ],
                '&important' => '1.2.3',
            ]));
        $result = $TC->getMulti(['x', 'y']);
        $this->assertEquals(['x' => 'x_value'], $result);
    }

    public function testGetMultiWithManyImportantKeysExpired() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            [ 'important1', 'important2' ],
            'unimportant'
        ]);
        $ConnMock->expects($this->once())
            ->method('__call')->with($this->equalTo('get'))
            ->will($this->returnValue([
                'x' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'x_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important2' => '1.2.3' ],
                ],
                'y' => [
                    Memcached\TaggedCache::SUBKEY_DATA => 'y_value',
                    Memcached\TaggedCache::SUBKEY_TAGS => [ '&important1' => '0' ],
                ],
                '&important1' => '99'
            ]));
        $result = $TC->getMulti(['x', 'y']);
        $this->assertEquals([], $result);
    }

    public function testSetOne() {
        $version = microtime(true);
        $ConnMock = $this->getConnectionMock($version);
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, ['important_{{ i(id) }}', 'unimportant_{{ i(id) }}']);
        $value = [
            Memcached\TaggedCache::SUBKEY_DATA => 1,
            Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => $version ],
        ];
        $map = [
            'a_1' => ['a_1', $value, null, null],
            '&important_1' => ['&important_1', $version, null, null],
            '&unimportant_1' => ['&unimportant_1', $version, null, null],
        ];
        $ConnMock->expects($this->exactly(3))
            ->method('__call')
            ->with($this->equalTo('set'))
            ->will($this->returnCallback(function($method, $args) use ($map, $version) {
                $this->assertEquals($map[$args[0]], $args);
            }));
        $TC->set('a_{{ i(id) }}', 1, ['id' => 1]);
    }

    public function testSetOneKey() {
        $version = microtime(true);
        $ConnMock = $this->getConnectionMock($version);
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, ['important_{{ i(id) }}', 'unimportant_{{ i(id) }}']);
        $value = [
            Memcached\TaggedCache::SUBKEY_DATA => 1,
            Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => $version ],
        ];
        $map = [
            'a_1' => ['a_1', $value, null, null],
            '&important_1' => ['&important_1', $version, null, null],
            '&unimportant_1' => ['&unimportant_1', $version, null, null],
        ];
        $ConnMock->expects($this->exactly(3))
            ->method('__call')
            ->with($this->equalTo('set'))
            ->will($this->returnCallback(function($method, $args) use ($map, $version) {
                $this->assertEquals($map[$args[0]], $args);
            }));
        $TC->setKey(Memcached\Key::create('a_{{ i(id) }}'), 1, ['id' => 1]);
    }

    public function testSetMulti() {
        $version = microtime(true);
        $ConnMock = $this->getConnectionMock($version);
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            [
                'important_{{ i(id) }}',
                'important2_{{ i(id) }}',
            ],
            'unimportant_{{ i(id) }}',
            'unimportant2_{{ i(id) }}',
        ]);
        $value_a = [
            Memcached\TaggedCache::SUBKEY_DATA => 1,
            Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => $version, '&important2_1' => $version ],
        ];
        $value_b = [
            Memcached\TaggedCache::SUBKEY_DATA => 2,
            Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => $version, '&important2_1' => $version ],
        ];
        $map = [
            'a_1' => ['a_1', $value_a, null, null],
            'b_1' => ['b_1', $value_b, null, null],
            '&important_1' => ['&important_1', $version, null, null],
            '&important2_1' => ['&important2_1', $version, null, null],
            '&unimportant_1' => ['&unimportant_1', $version, null, null],
            '&unimportant2_1' => ['&unimportant2_1', $version, null, null],
        ];
        $ConnMock->expects($this->exactly(6))
            ->method('__call')
            ->with($this->equalTo('set'))
            ->will($this->returnCallback(function($method, $args) use ($map, $version) {
                $this->assertEquals($map[$args[0]], $args);
            }));
        $TC->setMulti(['a_{{ i(id) }}' => 1, 'b_{{ i(id) }}' => 2], ['id' => 1]);
    }

    public function testSetMultiKeys() {
        $version = microtime(true);
        $ConnMock = $this->getConnectionMock($version);
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, [
            [
                'important_{{ i(id) }}',
                'important2_{{ i(id) }}',
            ],
            'unimportant_{{ i(id) }}',
            'unimportant2_{{ i(id) }}',
        ]);
        $value_a = [
            Memcached\TaggedCache::SUBKEY_DATA => 1,
            Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => $version, '&important2_1' => $version ],
        ];
        $value_b = [
            Memcached\TaggedCache::SUBKEY_DATA => 2,
            Memcached\TaggedCache::SUBKEY_TAGS => [ '&important_1' => $version, '&important2_1' => $version ],
        ];
        $map = [
            'a_1' => ['a_1', $value_a, null, null],
            'b_1' => ['b_1', $value_b, null, null],
            '&important_1' => ['&important_1', $version, null, null],
            '&important2_1' => ['&important2_1', $version, null, null],
            '&unimportant_1' => ['&unimportant_1', $version, null, null],
            '&unimportant2_1' => ['&unimportant2_1', $version, null, null],
        ];
        $ConnMock->expects($this->exactly(6))
            ->method('__call')
            ->with($this->equalTo('set'))
            ->will($this->returnCallback(function($method, $args) use ($map, $version) {
                $this->assertEquals($map[$args[0]], $args);
            }));
        $KeyA = Memcached\Key::create('a_{{ i(id) }}');
        $KeyB = Memcached\Key::create('b_{{ i(id) }}');
        $TC->setKeys(
            [$KeyA, $KeyB],
            [1, 2],
            ['id' => 1]
        );
    }

    public function testFlush() {
        $ConnMock = $this->getConnectionMock();
        /** @noinspection PhpParamsInspection */
        $TC = new Memcached\TaggedCache($ConnMock, ['important_{{ i(id) }}', 'unimportant_{{ i(id) }}']);
        $map = [
            '&important_1' => true,
            '&unimportant_1' => true,
        ];
        $ConnMock->expects($this->exactly(2))->method('__call')->with($this->equalTo('delete'))
            ->will($this->returnCallback(function($method, $args) use ($map) {
                $this->assertArrayHasKey($args[0], $map);
            }));
        $TC->flush(['id' => 1]);
    }

    protected function getConnectionMock($transaction_id = null) {
        $Mock = $this->getMockBuilder('\Carcass\Memcached\Connection')->disableOriginalConstructor()->getMock();
        $Mock->expects($this->any())->method('getTransactionId')->will($this->returnValue($transaction_id));
        return $Mock;
    }
}
