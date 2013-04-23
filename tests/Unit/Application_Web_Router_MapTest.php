<?php

use \Carcass\Application;
use \Carcass\Corelib;

class Application_Web_Router_MapTest extends PHPUnit_Framework_TestCase {

    private static $cfg = [
        'Index'             => '/',
        'Users.Default'     => '/users/',
        'Users.ById'        => '/users/{#id}',
        'Users.ByIdEx'      => '/users/{#id}-{$title}/{+extra}',
        'News'              => '/news/',
        'News.ByTitle'      => '/news/{$title}',
        'News.ByIdAndTitle' => '/news/id/{#id}[-{$title}]',
        'Search'            => '/search/{+q}',
        'Find'              => '/find/{*q}',
        'Nested'            => '/nested/[{#id}[-{$title}]]',
    ];

    public function testBaseRouting() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Index.Default'), $this->equalTo(new Corelib\Hash));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Users.Default'), $this->equalTo(new Corelib\Hash));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/users/'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('News.Default'), $this->equalTo(new Corelib\Hash));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/news/'), $C);
    }

    public function testIntegerArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Users.ById'), $this->equalTo(new Corelib\Hash(['id'=>1])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/users/1'), $C);
    }

    public function testUriPartArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('News.ByTitle'), $this->equalTo(new Corelib\Hash(['title'=>'foo'])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/news/foo'), $C);
    }

    public function testSuffixArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Search.Default'), $this->equalTo(new Corelib\Hash(['q'=>'1/2/3'])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/search/1/2/3'), $C);
    }

    public function testOptionalSuffixEmptyArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Find.Default'), $this->equalTo(new Corelib\Hash(['q'=>''])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/find/'), $C);
    }

    public function testOptionalSuffixArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Find.Default'), $this->equalTo(new Corelib\Hash(['q'=>'1/2/3'])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/find/1/2/3'), $C);
    }

    public function testMixedArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Users.ByIdEx'), $this->equalTo(new Corelib\Hash(['id'=>1,'title'=>'name','extra'=>'1/2/3'])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/users/1-name/1/2/3'), $C);
    }

    public function testOptionalArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('News.ByIdAndTitle'), $this->equalTo(new Corelib\Hash(['id'=>1,'title'=>'name'])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/news/id/1-name'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('News.ByIdAndTitle'), $this->equalTo(new Corelib\Hash(['id'=>1])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/news/id/1'), $C);
    }

    public function testNestedOptionalArgs() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Nested.Default'), $this->equalTo(new Corelib\Hash));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/nested/'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Nested.Default'), $this->equalTo(new Corelib\Hash(['id'=>1])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/nested/1'), $C);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatch')->with($this->equalTo('Nested.Default'), $this->equalTo(new Corelib\Hash(['id'=>1, 'title'=>'name'])));
        /** @noinspection PhpParamsInspection */
        $R->route($this->getRequest('/nested/1-name'), $C);
    }

    public function testNotFound() {
        $R = new Application\Web_Router_Map(self::$cfg);

        $C = $this->getControllerMock();
        $C->expects($this->once())->method('dispatchNotFound');
        /** @noinspection PhpParamsInspection */
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

    public function testBuildUrl() {
        $R = new Application\Web_Router_Map(self::$cfg);
        $this->assertEquals('/', $R->getUrl(new Corelib\Request, 'index'));
        $this->assertEquals('/', $R->getUrl(new Corelib\Request, 'index.default'));
        $this->assertEquals('/users/1', $R->getUrl(new Corelib\Request, 'users.byId', ['id'=>1]));
    }

    public function testAbsoluteUrl() {
        $R = new Application\Web_Router_Map(self::$cfg);
        $Request = new Corelib\Request([
            'Env' => [
                'HOST' => 'example.com'
            ]
        ]);
        $this->assertEquals('http://example.com/users/1', $R->getAbsoluteUrl($Request, 'users.byId', ['id'=>1]));
        $Request->Env->SCHEME = 'https';
        $this->assertEquals('https://example.com/users/1', $R->getAbsoluteUrl($Request, 'users.byId', ['id'=>1]));
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
