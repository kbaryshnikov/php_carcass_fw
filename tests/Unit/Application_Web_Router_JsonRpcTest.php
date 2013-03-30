<?php

use Carcass\Application;
use Carcass\Application\Web_Router_JsonRpc;
use Carcass\Corelib;
use Carcass\Http;

class Application_Web_Router_JsonRpcTest extends PHPUnit_Framework_TestCase {

    public function testRouterDispatchMethod() {
        $Controller = $this->getMockBuilder('\Carcass\Application\Web_JsonRpc_FrontController')->disableOriginalConstructor()->getMock();

        $Controller->expects($this->once())->method('dispatchRequestBody')
            ->with($this->isInstanceOf('\Carcass\Http\JsonRpc_Server'))
            ->will(
                $this->returnCallback(
                    function (Http\JsonRpc_Server $Server) {
                        $Server->dispatchRequestBody(
                            $this->getJsonBodyProviderFn(
                                [
                                    'jsonrpc' => '2.0',
                                    'id'      => 1,
                                    'method'  => 'group_controller-name.action-name',
                                    'params'  => ['a', 'b'],
                                ]
                            )
                        );
                    }
                )
            );

        $Controller->expects($this->once())->method('dispatch')
            ->with(
                $this->equalTo('Group_ControllerName.ActionName'), $this->callback(
                    function (Corelib\Hash $Args) {
                        return $Args[0] === 'a' && $Args[1] === 'b';
                    }
                )
            )
            ->will($this->returnValue(['ok' => 1]));

        $Controller->expects($this->once())->method('displayResponse')->will(
            $this->returnCallback(
                function (Http\JsonRpc_Server $Server) {
                    $response = $Server->getCollectedResponse();
                    $expected = [
                        'jsonrpc' => '2.0',
                        'result'  => ['ok' => 1],
                        'id'      => 1
                    ];
                    $this->assertEquals($expected, $response);
                }
            )
        );

        $Router = new Web_Router_JsonRpc('/api/');
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    public function testRouterDispatchBatch() {
        $Controller = $this->getMockBuilder('\Carcass\Application\Web_JsonRpc_FrontController')->disableOriginalConstructor()->getMock();

        $Controller->expects($this->once())->method('dispatchRequestBody')
            ->with($this->isInstanceOf('\Carcass\Http\JsonRpc_Server'))
            ->will(
                $this->returnCallback(
                    function (Http\JsonRpc_Server $Server) {
                        $Server->dispatchRequestBody(
                            $this->getJsonBodyProviderFn(
                                [
                                    [
                                        'jsonrpc' => '2.0',
                                        'id'      => 1,
                                        'method'  => 'call1',
                                        'params'  => ['1'],
                                    ],
                                    [
                                        'jsonrpc' => '2.0',
                                        'id'      => 2,
                                        'method'  => 'call2',
                                        'params'  => ['2'],
                                    ],
                                    [
                                        'jsonrpc' => '2.0',
                                        'id'      => null,
                                        'method'  => 'call3',
                                        'params'  => ['3'],
                                    ],
                                ]
                            )
                        );
                    }
                )
            );

        $Controller->expects($this->at(0))->method('dispatch')
            ->with(
                $this->equalTo('Call1'), $this->callback(
                    function (Corelib\Hash $Args) {
                        return $Args[0] === '1';
                    }
                )
            )
            ->will($this->returnValue(['ok' => 1]));
        $Controller->expects($this->at(1))->method('dispatch')
            ->with(
                $this->equalTo('Call2'), $this->callback(
                    function (Corelib\Hash $Args) {
                        return $Args[0] === '2';
                    }
                )
            )
            ->will($this->returnValue(['ok' => 2]));
        $Controller->expects($this->at(2))->method('dispatch')
            ->with(
                $this->equalTo('Call3'), $this->callback(
                    function (Corelib\Hash $Args) {
                        return $Args[0] === '3';
                    }
                )
            )
            ->will($this->returnValue(['ok' => 3]));

        $Controller->expects($this->once())->method('displayResponse')->will(
            $this->returnCallback(
                function (Http\JsonRpc_Server $Server) {
                    $response = $Server->getCollectedResponse();
                    $expected = [
                        [
                            'jsonrpc' => '2.0',
                            'result'  => ['ok' => 1],
                            'id'      => 1
                        ],
                        [
                            'jsonrpc' => '2.0',
                            'result'  => ['ok' => 2],
                            'id'      => 2
                        ],
                    ];
                    $this->assertEquals($expected, $response);
                }
            )
        );

        $Router = new Web_Router_JsonRpc('/api/');
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    public function testRouterDispatchNotFoundOnApiUriMismatch() {
        $Controller = $this->getMockBuilder('\Carcass\Application\Web_JsonRpc_FrontController')->disableOriginalConstructor()->getMock();

        $Controller->expects($this->once())->method('dispatchNotFound');
        $Router = new Web_Router_JsonRpc('/zzz/');
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    public function testRouterApiClassTemplate() {
        $Controller = $this->getMockBuilder('\Carcass\Application\Web_JsonRpc_FrontController')->disableOriginalConstructor()->getMock();

        $Controller->expects($this->once())->method('dispatchRequestBody')
            ->with($this->isInstanceOf('\Carcass\Http\JsonRpc_Server'))
            ->will(
                $this->returnCallback(
                    function (Http\JsonRpc_Server $Server) {
                        $Server->dispatchRequestBody(
                            $this->getJsonBodyProviderFn(
                                [
                                    'jsonrpc' => '2.0',
                                    'id'      => 1,
                                    'method'  => 'Foo',
                                ]
                            )
                        );
                    }
                )
            );
        $Controller->expects($this->once())->method('dispatch')->with('ZZZ_Foo');
        $Router = new Web_Router_JsonRpc('/api/');
        $Router->setApiClassTemplate('ZZZ_%s')->route($this->getRequestStub('/api/'), $Controller);
    }

    public function testActionReturnsTrue() {
        $Controller = $this->getMockBuilder('\Carcass\Application\Web_JsonRpc_FrontController')->disableOriginalConstructor()->getMock();

        $Controller->expects($this->once())->method('dispatchRequestBody')
            ->with($this->isInstanceOf('\Carcass\Http\JsonRpc_Server'))
            ->will(
                $this->returnCallback(
                    function (Http\JsonRpc_Server $Server) {
                        $Server->dispatchRequestBody(
                            $this->getJsonBodyProviderFn(
                                [
                                    'jsonrpc' => '2.0',
                                    'id'      => 1,
                                    'method'  => 'a',
                                ]
                            )
                        );
                    }
                )
            );

        $Controller->expects($this->once())->method('dispatch')
            ->will($this->returnValue(true));

        $Controller->expects($this->once())->method('displayResponse')->will(
            $this->returnCallback(
                function (Http\JsonRpc_Server $Server) {
                    $response = $Server->getCollectedResponse();
                    $expected = [
                        'jsonrpc' => '2.0',
                        'result'  => ['success' => true],
                        'id'      => 1
                    ];
                    $this->assertEquals($expected, $response);
                }
            )
        );

        $Router = new Web_Router_JsonRpc('/api/');
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    public function testActionReturnsFalse() {
        $Controller = $this->getMockBuilder('\Carcass\Application\Web_JsonRpc_FrontController')->disableOriginalConstructor()->getMock();

        $Controller->expects($this->once())->method('dispatchRequestBody')
            ->with($this->isInstanceOf('\Carcass\Http\JsonRpc_Server'))
            ->will(
                $this->returnCallback(
                    function (Http\JsonRpc_Server $Server) {
                        $Server->dispatchRequestBody(
                            $this->getJsonBodyProviderFn(
                                [
                                    'jsonrpc' => '2.0',
                                    'id'      => 1,
                                    'method'  => 'a',
                                ]
                            )
                        );
                    }
                )
            );

        $Controller->expects($this->once())->method('dispatch')
            ->will($this->returnValue(false));

        $Controller->expects($this->once())->method('displayResponse')->will(
            $this->returnCallback(
                function (Http\JsonRpc_Server $Server) {
                    $response = $Server->getCollectedResponse();
                    $expected = [
                        'jsonrpc' => '2.0',
                        'result'  => ['success' => false],
                        'id'      => 1
                    ];
                    $this->assertEquals($expected, $response);
                }
            )
        );

        $Router = new Web_Router_JsonRpc('/api/');
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    public function testNoResponseForAbsentRequestId() {
        $Controller = $this->getMockBuilder('\Carcass\Application\Web_JsonRpc_FrontController')->disableOriginalConstructor()->getMock();

        $Controller->expects($this->once())->method('dispatchRequestBody')
            ->with($this->isInstanceOf('\Carcass\Http\JsonRpc_Server'))
            ->will(
                $this->returnCallback(
                    function (Http\JsonRpc_Server $Server) {
                        $Server->dispatchRequestBody(
                            $this->getJsonBodyProviderFn(
                                [
                                    'jsonrpc' => '2.0',
                                    'method'  => 'a',
                                ]
                            )
                        );
                    }
                )
            );

        $Controller->expects($this->once())->method('dispatch')
            ->will($this->returnValue(false));

        $Controller->expects($this->once())->method('displayResponse')->will(
            $this->returnCallback(
                function (Http\JsonRpc_Server $Server) {
                    $response = $Server->getCollectedResponse();
                    $expected = [];
                    $this->assertEquals($expected, $response);
                }
            )
        );

        $Router = new Web_Router_JsonRpc('/api/');
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    protected function getRequestStub($uri) {
        return new Corelib\Request(
            [
                'Env' => [
                    'REQUEST_URI' => $uri,
                ]
            ]
        );
    }

    protected function getJsonBodyProviderFn(array $request) {
        return function () use ($request) {
            return json_encode($request);
        };
    }

}

