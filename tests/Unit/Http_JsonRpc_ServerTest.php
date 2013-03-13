<?php

use \Carcass\Http;
use \Carcass\Corelib;

class Http_JsonRpc_ServerTest extends PHPUnit_Framework_TestCase {

    public function testDispatchString() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $expected = [
                            'jsonrpc' => '2.0',
                            'id'      => 1,
                            'result'  => ['success' => true],
                        ];
                        $actual   = json_decode($output, true);
                        $this->assertEquals($expected, $actual);
                    }
                )
            );

        $DispatcherFn = function ($method, Corelib\Hash $Args) {
            $this->assertEquals('test', $method);
            $this->assertEquals(['test_key' => 'test_value'], $Args->exportArray());
            return ['success' => true];
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'test',
                        'params'  => ['test_key' => 'test_value'],
                        'id'      => 1,
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testDispatcherFunctionReturnsBooleanTrue() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $expected = [
                            'jsonrpc' => '2.0',
                            'id'      => 1,
                            'result'  => ['success' => true],
                        ];
                        $actual   = json_decode($output, true);
                        $this->assertEquals($expected, $actual);
                    }
                )
            );

        $DispatcherFn = function ($method, Corelib\Hash $Args) {
            return true;
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'test',
                        'params'  => ['test_key' => 'test_value'],
                        'id'      => 1,
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testDispatcherFunctionReturnsBooleanFalse() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $expected = [
                            'jsonrpc' => '2.0',
                            'id'      => 1,
                            'result'  => ['success' => false],
                        ];
                        $actual   = json_decode($output, true);
                        $this->assertEquals($expected, $actual);
                    }
                )
            );

        $DispatcherFn = function ($method, Corelib\Hash $Args) {
            return false;
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'test',
                        'params'  => ['test_key' => 'test_value'],
                        'id'      => 1,
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testDispatcherFunctionReturnsGarbage() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $json = json_decode($output, true);
                        $this->assertArrayHasKey('error', $json);
                        $this->assertEquals(Http\JsonRpc_Exception::ERR_SERVER_ERROR, $json['error']['code']);
                    }
                )
            );

        $DispatcherFn = function ($method, Corelib\Hash $Args) {
            return 'foo';
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'test',
                        'params'  => ['test_key' => 'test_value'],
                        'id'      => 1,
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testDispatchRequestBody() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $expected = [
                            'jsonrpc' => '2.0',
                            'id'      => 1,
                            'result'  => ['success' => true],
                        ];
                        $actual   = json_decode($output, true);
                        $this->assertEquals($expected, $actual);
                    }
                )
            );

        $DispatcherFn = function ($method, Corelib\Hash $Args) {
            $this->assertEquals('test', $method);
            $this->assertEquals(0, count($Args));
            return ['success' => true];
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchRequestBody(
                function () {
                    return
                        json_encode(
                            [
                                'jsonrpc' => '2.0',
                                'method'  => 'test',
                                'id'      => 1,
                            ]
                        );
                }
            )
            ->displayTo($Response);
    }

    public function testMissingJsonRpcVersion() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $json = json_decode($output, true);
                        $this->assertEquals(Http\JsonRpc_Exception::ERR_INVALID_REQUEST, $json['error']['code']);
                    }
                )
            );

        $DispatcherFn = function () {
            throw new \Exception("Must not be called");
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'method' => 'test',
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testMissingMethod() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $json = json_decode($output, true);
                        $this->assertEquals(Http\JsonRpc_Exception::ERR_INVALID_REQUEST, $json['error']['code']);
                    }
                )
            );

        $DispatcherFn = function () {
            throw new \Exception("Must not be called");
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testInvalidMethod() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $json = json_decode($output, true);
                        $this->assertEquals(Http\JsonRpc_Exception::ERR_INVALID_REQUEST, $json['error']['code']);
                    }
                )
            );

        $DispatcherFn = function () {
            throw new \Exception("Must not be called");
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => [1, 2, 3]
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testNoOutputIfIdIsMissing() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $json = json_decode($output, true);
                        $this->assertEmpty($json);
                    }
                )
            );

        $DispatcherFn = function () {
            return ['success' => true];
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'notify',
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testNoOutputIfIdIsNull() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $json = json_decode($output, true);
                        $this->assertEmpty($json);
                    }
                )
            );

        $DispatcherFn = function () {
            return ['success' => true];
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'notify',
                        'id'      => null,
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testCatchAllExceptionMode() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $json = json_decode($output, true);
                        $this->assertArrayHasKey('error', $json);
                    }
                )
            );

        $DispatcherFn = function () {
            throw new \Exception;
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->catchAllExceptions()
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'test',
                        'id'      => 1,
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testCatchAllExceptionModeDisabled() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->never())
            ->method('write');

        $DispatcherFn = function () {
            throw new \Exception;
        };

        $this->setExpectedException('\Exception');

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'test',
                        'id'      => 1,
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testBatch() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $expected = [
                            [
                                'jsonrpc' => '2.0',
                                'id'      => 1,
                                'result'  => ['success' => 'a'],
                            ],
                            [
                                'jsonrpc' => '2.0',
                                'id'      => 2,
                                'result'  => ['success' => 'b', 'args' => ['x' => 'y']],
                            ],
                        ];
                        $actual   = json_decode($output, true);
                        $this->assertEquals($expected, $actual);
                    }
                )
            );

        $DispatcherFn = function ($method, Corelib\Hash $Args) {
            $result = ['success' => $method];
            if (count($Args)) {
                $result['args'] = $Args->exportArray();
            }
            return $result;
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        [
                            'jsonrpc' => '2.0',
                            'method'  => 'a',
                            'id'      => 1,
                        ],
                        [
                            'jsonrpc' => '2.0',
                            'method'  => 'b',
                            'id'      => 2,
                            'params'  => ['x' => 'y'],
                        ]
                    ]
                )
            )
            ->displayTo($Response);
    }

    public function testBatchAbortException() {
        $Response = $this->getMock('\Carcass\Corelib\ResponseInterface');
        $Response
            ->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($output) {
                        $expected = [
                            [
                                'jsonrpc' => '2.0',
                                'id'      => 1,
                                'result'  => ['success' => true],
                            ],
                            [
                                'jsonrpc' => '2.0',
                                'id'      => 2,
                                'error'   => [
                                    'code'    => Http\JsonRpc_Exception::ERR_BATCH_ABORTED,
                                    'message' => 'Server error: Batch aborted'
                                ]
                            ],
                            [
                                'jsonrpc' => '2.0',
                                'id'      => 3,
                                'error'   => [
                                    'code'    => Http\JsonRpc_Exception::ERR_BATCH_ABORTED,
                                    'message' => 'Server error: Batch aborted'
                                ]
                            ],
                        ];
                        $actual   = json_decode($output, true);
                        $this->assertEquals($expected, $actual);
                    }
                )
            );

        $DispatcherFn = function ($method, Corelib\Hash $Args) {
            if ($method != 'fail') {
                return true;
            }
            throw Http\JsonRpc_Exception::constructAbortBatch('Batch aborted');
        };

        (new Http\JsonRpc_Server($DispatcherFn))
            ->dispatchJsonString(
                json_encode(
                    [
                        [
                            'jsonrpc' => '2.0',
                            'method'  => 'a',
                            'id'      => 1,
                        ],
                        [
                            'jsonrpc' => '2.0',
                            'method'  => 'fail',
                            'id'      => 2,
                            'params'  => ['x' => 'y'],
                        ],
                        [
                            'jsonrpc' => '2.0',
                            'method'  => 'c',
                            'id'      => 3,
                        ]
                    ]
                )
            )
            ->displayTo($Response);
    }
}