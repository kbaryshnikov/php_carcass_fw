<?php

use \Carcass\Corelib;

class Corelib_UrlTest extends PHPUnit_Framework_TestCase {

    public function testBuildUrl() {
        $this->assertEquals(
            '/foo/1?a=1',
            (new Corelib\Url('/{$s}/{#i}', ['s'=>'foo', 'i'=>1], ['a'=>1]))->getRelative()
        );
    }

    public function testAbsolute() {
        $this->assertEquals(
            'http://example.com/foo/1?a=1',
            (new Corelib\Url('/{$s}/{#i}', ['s'=>'foo', 'i'=>1], ['a'=>1]))->getAbsolute('example.com')
        );
        $this->assertEquals(
            'https://example.com/foo/1?a=1',
            (new Corelib\Url('/{$s}/{#i}', ['s'=>'foo', 'i'=>1], ['a'=>1]))->getAbsolute('example.com', 'https')
        );
        $this->assertEquals(
            'https://user@example.com/foo/1?a=1',
            (new Corelib\Url('/{$s}/{#i}', ['s'=>'foo', 'i'=>1], ['a'=>1]))->getAbsolute('example.com', 'https', 'user')
        );
        $this->assertEquals(
            'https://user:pass@example.com:8080/foo/1?a=1',
            (new Corelib\Url('/{$s}/{#i}', ['s'=>'foo', 'i'=>1], ['a'=>1]))->getAbsolute('example.com:8080', 'https', 'user', 'pass')
        );
    }

    public function testAddQs() {
        $this->assertEquals('/?a=1', Corelib\Url::constructRaw('/')->addQueryString(['a'=>1])->getRelative());
        $this->assertEquals('/?a=1#x', Corelib\Url::constructRaw('/')->addQueryString(['a'=>1, '#'=>'x'])->getRelative());
        $this->assertEquals('/?a=1', Corelib\Url::constructRaw('/?b=2')->addQueryString(['a'=>1, 'b'=>null])->getRelative());
        $this->assertEquals('/', Corelib\Url::constructRaw('/?b=2')->addQueryString(['b'=>null])->getRelative());
    }

}
