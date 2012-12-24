<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Application as Application;
use \Carcass\Corelib as Corelib;

class Application_Web_Router_MapTest extends PHPUnit_Framework_TestCase {

    private static $cfg = [
        'Index'             => '/',
        'Users.Default'     => '/users/',
        'Users.byId'        => '/users/{#id}',
        'Users.byIdEx'      => '/users/{#id}-{$title}/{+extra}',
        'News'              => '/news/',
        'News.byTitle'      => '/news/{$title}',
        'News.byIdAndTitle' => '/news/id/{#id}[-{$title}]',
        'Search'            => '/search/{+q}',
        'Nested'            => '/nested/[{#id}[-{$title}]]',
    ];

    public function testBaseRouting() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Index.Default'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Users.Default'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/users/'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('News.Default'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/news/'), $C);
    }

    public function testIntegerArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Users.byId'), $this->equalTo(new Corelib\Hash(['id'=>1])));
        $R->route($this->getRequest('/users/1'), $C);
    }

    public function testUriPartArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('News.byTitle'), $this->equalTo(new Corelib\Hash(['title'=>'foo'])));
        $R->route($this->getRequest('/news/foo'), $C);
    }

    public function testSuffixArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Search.Default'), $this->equalTo(new Corelib\Hash(['q'=>'1/2/3'])));
        $R->route($this->getRequest('/search/1/2/3'), $C);
    }

    public function testMixedArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Users.byIdEx'), $this->equalTo(new Corelib\Hash(['id'=>1,'title'=>'name','extra'=>'1/2/3'])));
        $R->route($this->getRequest('/users/1-name/1/2/3'), $C);
    }

    public function testOptionalArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('News.byIdAndTitle'), $this->equalTo(new Corelib\Hash(['id'=>1,'title'=>'name'])));
        $R->route($this->getRequest('/news/id/1-name'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('News.byIdAndTitle'), $this->equalTo(new Corelib\Hash(['id'=>1])));
        $R->route($this->getRequest('/news/id/1'), $C);
    }

    public function testNestedOptionalArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Nested.Default'), $this->equalTo(new Corelib\Hash));
        $R->route($this->getRequest('/nested/'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Nested.Default'), $this->equalTo(new Corelib\Hash(['id'=>1])));
        $R->route($this->getRequest('/nested/1'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Nested.Default'), $this->equalTo(new Corelib\Hash(['id'=>1, 'title'=>'name'])));
        $R->route($this->getRequest('/nested/1-name'), $C);
    }

    public function testNotFound() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatchNotFound');
        $R->route($this->getRequest('/miss/'), $C);
    }

    public function testBrokenMapMissingSlash() {
        $this->setExpectedException('RuntimeException');
        $R = new Application\Web_Router_Map([
            'aaa' => 'foo'
        ]);
    }

    public function testBrokenMapBadArgs() {
        $this->setExpectedException('RuntimeException');
        $R = new Application\Web_Router_Map([
            '/{}' => 'foo'
        ]);
    }

    protected function getControllerMock() {
        return $this->getMock('\Carcass\Application\ControllerInterface', array('dispatch', 'dispatchNotFound'));
    }

    protected function getRequest($uri) {
        return new Corelib\Request([
            'Env' => [
                'DOCUMENT_URI' => $uri
            ]
        ]);
    }

}
