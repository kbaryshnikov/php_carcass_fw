<?php

use \Carcass\Application;
use \Carcass\Corelib;
use \Carcass\Http;

class Application_Web_JsonRpcFrontControllerTest extends PHPUnit_Framework_TestCase {

    public function testJsonRpcResultsAreDisplayedToResponse() {
        $Request = $this->getMock('\Carcass\Corelib\Request');
        $Response = $this->getMockBuilder('\Carcass\Application\Web_Response')->disableOriginalConstructor()->getMock();
        $Router = $this->getMockBuilder('\Carcass\Application\Web_Router_JsonRpc')->disableOriginalConstructor()->getMock();
        $Config = $this->getMock('\Carcass\Config\ItemInterface');

        $Server = $this->getMockBuilder('\Carcass\Http\JsonRpc_Server')->disableOriginalConstructor()->getMock();
        $Server->expects($this->once())
            ->method('displayTo');

        $Ctrl = new TestJsonRpcFrontController($Request, $Response, $Router, $Config);
        $Ctrl->dispatch('TestJsonRpcController.Test', new Corelib\Hash);
        $Ctrl->displayJsonRpcResults($Server);
    }

}

class TestJsonRpcFrontController extends Application\Web_JsonRpcFrontController {

    protected function requirePageClass($page_class) {
        return $page_class;
    }

}

class TestJsonRpcControllerPage extends Application\Web_PageController {

    public function actionTest() {
        return true;
    }

}
