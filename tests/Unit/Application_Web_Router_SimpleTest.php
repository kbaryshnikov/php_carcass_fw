<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Application as Application;
use \Carcass\Corelib as Corelib;

class Application_Web_Router_SimpleTest extends PHPUnit_Framework_TestCase {

    public function testSimpleRouting() {
        $R = new Application\Web_Router_Simple;

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Foo'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/foo'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Foo'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/foo'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Foo_Bar'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/foo/bar'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Foo_Bar'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/foo/bar/'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Foo_Bar.act'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/foo/bar.act'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Default'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Foo_Bar'), $this->equalTo(new Corelib\Hash(['a'=>1,'b'=>'b'])));
        $R->route($this->getRequest('/foo/bar/', ['a'=>1,'b'=>'b']), $C);
    }

    protected function getControllerMock() {
        return $this->getMock('\Carcass\Application\ControllerInterface', array('dispatch', 'dispatchNotFound'));
    }

    protected function getRequest($uri, array $args = []) {
        return new Corelib\Request([
            'Env' => [
                'REQUEST_URI' => $uri
            ],
            'Args' => $args
        ]);
    }

}
