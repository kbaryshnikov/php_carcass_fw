<?php

use \Carcass\Memcached;

class Memcached_TaggedListCacheTest extends PHPUnit_Framework_TestCase {

    public function testNoIntersectionFullGet() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value = '|' . $key_tpl . '|0',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value, $key_count) {
                        return [
                            $key_value => range(0, 9),
                            $key_count => 10,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 0, 10);
        $this->assertEquals(range(0, 9), $result);
        $this->assertFalse($TLC->isIncomplete());
        $this->assertEquals(10, $TLC->getCount());
    }

    public function testGetReturnsFalseWhenCountIsMissingFromCache() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value = '|' . $key_tpl . '|0',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value, $key_count) {
                        return [
                            $key_value => range(0, 9),
                            $key_count => false,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 0, 10);
        $this->assertFalse($result);
    }

    public function testChunkSizeChange() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value_1 = '|' . $key_tpl . '|0',
                    5       => $key_value_2 = '|' . $key_tpl . '|5',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value_1, $key_value_2, $key_count) {
                        return [
                            $key_value_1 => range(0, 4),
                            $key_value_2 => array_combine(range(5, 9), range(5, 9)),
                            $key_count => 10,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $TLC->setChunkSize(5);

        $result = $TLC->get($args, 0, 10);
        $this->assertEquals(range(0, 9), $result);
        $this->assertFalse($TLC->isIncomplete());
        $this->assertEquals(10, $TLC->getCount());

    }

    public function testNoIntersectionPartialGet() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value = '|' . $key_tpl . '|0',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value, $key_count) {
                        return [
                            $key_value => range(0, 9),
                            $key_count => 10,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 0, 5);
        $this->assertEquals(range(0, 4), $result);
        $this->assertFalse($TLC->isIncomplete());
        $this->assertEquals(10, $TLC->getCount());
    }

    public function testFullIntersectionFullGet() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value1 = '|' . $key_tpl . '|0',
                    10      => $key_value2 = '|' . $key_tpl . '|10',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value1, $key_value2, $key_count) {
                        return [
                            $key_value1 => range(0, 9),
                            $key_value2 => array_combine(range(10, 19), range(10, 19)),
                            $key_count  => 20,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 0, 20);
        $this->assertEquals(range(0, 19), $result);
        $this->assertEquals(20, $TLC->getCount());
    }

    public function testFullIntersectionPartialGet() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value1 = '|' . $key_tpl . '|0',
                    10      => $key_value2 = '|' . $key_tpl . '|10',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value1, $key_value2, $key_count) {
                        return [
                            $key_value1 => range(0, 9),
                            $key_value2 => array_combine(range(10, 19), range(10, 19)),
                            $key_count  => 20,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 0, 15);
        $this->assertEquals(range(0, 14), $result);
        $this->assertFalse($TLC->isIncomplete());
        $this->assertEquals(20, $TLC->getCount());
    }

    public function testPartialIntersectionGet() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value1 = '|' . $key_tpl . '|0',
                    10      => $key_value2 = '|' . $key_tpl . '|10',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value1, $key_value2, $key_count) {
                        return [
                            $key_value1 => range(0, 9),
                            $key_value2 => array_combine(range(10, 19), range(10, 19)),
                            $key_count  => 20,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 1, 10);
        $this->assertEquals(array_combine(range(1, 10), range(1, 10)), $result);
        $this->assertFalse($TLC->isIncomplete());
        $this->assertEquals(20, $TLC->getCount());
    }

    public function testPartialIntersectionIncompletePartialGet() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value1 = '|' . $key_tpl . '|0',
                    10      => $key_value2 = '|' . $key_tpl . '|10',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value1, $key_value2, $key_count) {
                        return [
                            $key_value1 => range(0, 9),
                            $key_value2 => false,
                            $key_count  => 20,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 1, 10, true);
        $this->assertEquals(array_combine(range(1, 9), range(1, 9)), $result);
        $this->assertTrue($TLC->isIncomplete());
        $this->assertEquals(20, $TLC->getCount());
    }

    public function testIncompleteGetReturnsFalseUnlessReturnIncompleteFlagGiven() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value1 = '|' . $key_tpl . '|0',
                    10      => $key_value2 = '|' . $key_tpl . '|10',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value1, $key_value2, $key_count) {
                        return [
                            $key_value1 => range(0, 9),
                            $key_value2 => false,
                            $key_count  => 20,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 1, 10);
        $this->assertEquals(false, $result);
        $this->assertTrue($TLC->isIncomplete());
        $this->assertEquals(20, $TLC->getCount());
    }

    public function testNoIntersectionIncompletePartialGet() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value1 = '|' . $key_tpl . '|0',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value1, $key_count) {
                        return [
                            $key_value1 => false,
                            $key_count  => 20,
                        ];
                    }
                )
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $result = $TLC->get($args, 0, 10, true);
        $this->assertEquals([], $result);
        $this->assertTrue($TLC->isIncomplete());
        $this->assertEquals(20, $TLC->getCount());
    }

    public function testNoIntersectionSetWithNoCountGiven() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('setMulti')
            ->with(
                [
                    '|' . $key_tpl . '|0' => range(0, 9),
                    '|' . $key_tpl . '|#' => 10,
                ],
                $args
            )->will(
                $this->returnValue(true)
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $this->assertTrue($TLC->set($args, range(0, 9)));
        $this->assertEquals(10, $TLC->getCount());
    }

    public function testNoIntersectionSetWithCountGiven() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('setMulti')
            ->with(
                [
                    '|' . $key_tpl . '|0' => range(0, 8),
                    '|' . $key_tpl . '|#' => 9,
                ],
                $args
            )->will(
                $this->returnValue(true)
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $TLC->setCount(9);
        $this->assertTrue($TLC->set($args, range(0, 9)));
        $this->assertEquals(9, $TLC->getCount());
    }

    public function testExceptionIsThrownWhenSettingWithOffsetAndNoCountGiven() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->never())
            ->method('setMulti');

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $this->setExpectedException('\LogicException', 'Count is undefined');
        $TLC->set($args, range(0, 10), 1);
    }

    public function testSetWithIntersection() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    10      => $key_value2 = '|' . $key_tpl . '|10',
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value2) {
                        return [
                            $key_value2 => [10 => -10, 11],
                        ];
                    }
                )
            );

        $TCMock->expects($this->once())
            ->method('setMulti')
            ->with(
                [
                    '|' . $key_tpl . '|0' => range(0, 9),
                    '|' . $key_tpl . '|10' => array_combine(range(10, 11), range(10, 11)),
                    '|' . $key_tpl . '|#' => 12,
                ],
                $args
            )->will(
                $this->returnValue(true)
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $TLC->setCount(12);

        $this->assertTrue($TLC->set($args, range(0, 10)));
    }

    public function testSetWithIntersectionAndOffset() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value1 = '|' . $key_tpl . '|0',
                    10      => $key_value2 = '|' . $key_tpl . '|10',
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value1, $key_value2) {
                        return [
                            $key_value1 => [0, -1, -2, -3, -4, -5, -6, -7, -8, -9],
                            $key_value2 => [10 => -10, 11],
                        ];
                    }
                )
            );

        $TCMock->expects($this->once())
            ->method('setMulti')
            ->with(
                [
                    '|' . $key_tpl . '|0' => range(0, 9),
                    '|' . $key_tpl . '|10' => array_combine(range(10, 11), range(10, 11)),
                    '|' . $key_tpl . '|#' => 12,
                ],
                $args
            )->will(
                $this->returnValue(true)
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $TLC->setCount(12);

        $this->assertTrue($TLC->set($args, range(1, 10), 1));
    }

    public function testPartialSetUsesCountValueFromLastGetCall() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->at(0))
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value = '|' . $key_tpl . '|0',
                    'count' => $key_count = '|' . $key_tpl . '|#'
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value, $key_count) {
                        return [
                            $key_value => range(0, 9),
                            $key_count => 10,
                        ];
                    }
                )
            );

        $TCMock->expects($this->at(1))
            ->method('getMulti')
            ->with(
                [
                    0       => $key_value = '|' . $key_tpl . '|0',
                ],
                $args
            )->will(
                $this->returnCallback(
                    function () use ($key_value, $key_count) {
                        return [
                            $key_value => range(0, 9),
                        ];
                    }
                )
            );

        $TCMock->expects($this->once())
            ->method('setMulti')
            ->with(
                [
                    '|' . $key_tpl . '|0' => [1 => 'new 1'] + range(0, 9),
                    '|' . $key_tpl . '|#' => 10,
                ],
                $args
            )->will(
                $this->returnValue(true)
            );

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        $TLC = new Memcached\TaggedListCache($TCMock, $key_tpl);

        $TLC->get($args, 0, 10);
        $TLC->set($args, [1 => 'new 1'], 1);
    }

    public function testDelete() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('set')
            ->with('|' . $key_tpl . '|#', false, $args);

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        (new Memcached\TaggedListCache($TCMock, $key_tpl))->delete($args);
    }

    public function testFlush() {
        $TCMock = $this->getTaggedCacheMock();

        $key_tpl = 'foo_{{ i(id) }}';
        $args    = ['id' => 1];

        $TCMock->expects($this->once())
            ->method('flush')
            ->with($args);

        /** @var $TCMock \Carcass\Memcached\TaggedCache */
        (new Memcached\TaggedListCache($TCMock, $key_tpl))->flush($args);
    }

    protected function getTaggedCacheMock() {
        return $this->getMockBuilder('\Carcass\Memcached\TaggedCache')
            ->disableOriginalConstructor()
            ->getMock();
    }

}