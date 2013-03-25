<?php

use \Carcass\Application;
use \Carcass\Application\Web_Router_JsonRpc;
use \Carcass\Corelib;

class Application_Web_Router_JsonRpcTest extends PHPUnit_Framework_TestCase {

    public function testRouterDispatchMethod() {
        $Controller = $this->getControllerMock();
        $Controller->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo('Group_ControllerName.ActionName'),
                $this->equalTo(new Corelib\Hash(['a', 'b']))
            );
        $Router = new Web_Router_JsonRpc('/api/');
        $Router->setRequestBodyProvider(
            $this->getRequestBodyProvider(
                [
                    'jsonrpc' => '2.0',
                    'method'  => 'group_controller-name.action-name',
                    'params'  => ['a', 'b'],
                ]
            )
        );
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    public function testRouterDispatchBatch() {
        $Controller = $this->getControllerMock();
        $Controller->expects($this->at(0))
            ->method('dispatch')
            ->with(
                $this->equalTo('Controller1'),
                $this->equalTo(new Corelib\Hash(['a1', 'b1']))
            );
        $Controller->expects($this->at(1))
            ->method('dispatch')
            ->with(
                $this->equalTo('Controller2'),
                $this->equalTo(new Corelib\Hash(['a2', 'b2']))
            );
        $Router = new Web_Router_JsonRpc('/api/');
        $Router->setRequestBodyProvider(
            $this->getRequestBodyProvider(
                [
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'controller1',
                        'params'  => ['a1', 'b1'],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'method'  => 'controller2',
                        'params'  => ['a2', 'b2'],
                    ],
                ]
            )
        );
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    public function testRouterDispatchesNotFoundOnApiUriMismatch() {
        $Controller = $this->getControllerMock();
        $Controller->expects($this->never())
            ->method('dispatch');
        $Controller->expects($this->once())
            ->method('dispatchNotFound');
        $Router = new Web_Router_JsonRpc('/api/');
        $Router->setRequestBodyProvider(
            $this->getRequestBodyProvider(
                [
                    'jsonrpc' => '2.0',
                    'method'  => 'm',
                ]
            )
        );
        $Router->route($this->getRequestStub('/'), $Controller);
    }

    public function testApiClassTemplate() {
        $Controller = $this->getControllerMock();
        $Controller->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo('Api_ControllerName'),
                $this->equalTo(new Corelib\Hash(['a', 'b']))
            );
        $Router = new Web_Router_JsonRpc('/api/');
        $Router->setApiClassTemplate('Api_%s');
        $Router->setRequestBodyProvider(
            $this->getRequestBodyProvider(
                [
                    'jsonrpc' => '2.0',
                    'method'  => 'controller-name',
                    'params'  => ['a', 'b'],
                ]
            )
        );
        $Router->route($this->getRequestStub('/api/'), $Controller);
    }

    protected function getRequestBodyProvider(array $json_data) {
        $json = json_encode($json_data);
        return function () use ($json) {
            return $json;
        };
    }

    protected function getControllerMock() {
        $Controller = $this->getMockBuilder('\Carcass\Application\Web_FrontController')
            ->disableOriginalConstructor()
            ->getMock();
        return $Controller;
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

}

