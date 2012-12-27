<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Corelib;

class Corelib_UrlTemplateTest extends PHPUnit_Framework_TestCase {

    public function testBuildUrlTemplate() {
        $this->assertEquals(
            '/foo',
            Corelib\UrlTemplate::build('/foo')
        );
        $this->assertEquals(
            '/foo/1',
            Corelib\UrlTemplate::build('/foo/{#i}', ['i'=>1])
        );
        $this->assertEquals(
            '/foo/aaa',
            Corelib\UrlTemplate::build('/foo/{$var}', ['var'=>'aaa'])
        );
        $this->assertEquals(
            '/foo/a%2Fa',
            Corelib\UrlTemplate::build('/foo/{$var}', ['var'=>'a/a'])
        );
        $this->assertEquals(
            '/foo/a/a',
            Corelib\UrlTemplate::build('/foo/{+var}', ['var'=>'a/a'])
        );
    }

    public function testOptionalArgs() {
        $this->assertEquals(
            '/foo/a',
            Corelib\UrlTemplate::build('/foo/{$a}[/{$b}]', ['a' => 'a'])
        );
        $this->assertEquals(
            '/foo/a/b',
            Corelib\UrlTemplate::build('/foo/{$a}[/{$b}]', ['a' => 'a', 'b' => 'b'])
        );
        $this->assertEquals(
            '/foo/',
            Corelib\UrlTemplate::build('/foo[/{$a}[/{$b}]]/', [])
        );
    }

    public function testQueryString() {
        $this->assertEquals(
            '/?a=1&b=%2F',
            Corelib\UrlTemplate::build('/', [], ['a'=>1, 'b'=>'/'])
        );
    }

    public function testArgsToQueryString() {
        $this->assertEquals(
            '/?a=1&b=%2F',
            Corelib\UrlTemplate::build('/', ['a'=>1, 'b'=>'/'], [], true)
        );
        $this->assertEquals(
            '/?a=1&b=%2F',
            Corelib\UrlTemplate::build('/', ['a'=>1], ['b'=>'/'], true)
        );
        $this->assertEquals(
            '/1?b=%2F',
            Corelib\UrlTemplate::build('/{#a}', ['a'=>1], ['b'=>'/'], true)
        );
        $this->assertEquals(
            '/1?a=2&b=%2F',
            Corelib\UrlTemplate::build('/{#a}', ['a'=>1], ['a'=>2, 'b'=>'/'], true)
        );
        $this->assertEquals(
            '/?a=2&b=%2F',
            Corelib\UrlTemplate::build('/', ['a'=>1], ['a'=>2, 'b'=>'/'], true)
        );
    }

}
