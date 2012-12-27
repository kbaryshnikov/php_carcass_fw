<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Application;
use \Carcass\Corelib;

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

    public function testBuildUrl() {
        $R = new Application\Web_Router_Simple;
        $this->assertEquals('/a/b/c.d?a=1&b=2', $R->getUrl(new Corelib\Request, 'a_b_c.d', ['a'=>1,'b'=>2]));
    }

    public function testBuildAbsoluteUrl() {
        $R = new Application\Web_Router_Simple;
        $Request = new Corelib\Request([
            'Env' => [
                'HOST' => 'example.com'
            ]
        ]);
        $this->assertEquals('http://example.com/a/b/c.d?a=1&b=2', $R->getAbsoluteUrl($Request, 'a_b_c.d', ['a'=>1,'b'=>2]));
        $Request->Env->SCHEME = 'https';
        $this->assertEquals('https://example.com/a/b/c.d?a=1&b=2', $R->getAbsoluteUrl($Request, 'a_b_c.d', ['a'=>1,'b'=>2]));
    }

    protected function getControllerMock() {
        return $this->getMock('\Carcass\Application\ControllerInterface', array('dispatch', 'dispatchNotFound'));
    }

    protected function getRequest($uri = '/', array $args = []) {
        return new Corelib\Request([
            'Env' => [
                'REQUEST_URI' => $uri
            ],
            'Args' => $args
        ]);
    }

}
