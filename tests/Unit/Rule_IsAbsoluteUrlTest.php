<?php

use \Carcass\Rule\IsAbsoluteUrl;

class Filter_UrlNormalizeTest extends PHPUnit_Framework_TestCase {

    public function testWellFormedPath() {
        $noUnescape = ['?', '#', '[', ']'];
        $unescape = [':', '/', '!', '$', '&', "'", '(', ')', '*', '+', ',', ';', '=', '~'];
        $url = 'http://domain.tld/%2B' . join('', array_map('urlencode', $noUnescape)) . '-' . join('', array_map('urlencode', $unescape));
        $this->assertTrue((new IsAbsoluteUrl())->validate($url));
    }

    public function testWellFormedPathWithQueryString() {
        $noUnescape = ['?', '#', '[', ']'];
        $unescape = [':', '/', '!', '$', '&', "'", '(', ')', '*', '+', ',', ';', '=', '~'];
        $url = 'http://domain.tld/%2B' . join('', array_map('urlencode', $noUnescape)) . '-' . join('', array_map('urlencode', $unescape)) . '?a=b&c=%2b&z=&d#+%';
        $this->assertTrue((new IsAbsoluteUrl())->validate($url));
    }

    public function testMalformedUnescapedPath() {
        $url = 'http://domain.tld/%';
        $this->assertFalse((new IsAbsoluteUrl())->validate($url));
        $url = 'http://domain.tld/?%';
        $this->assertFalse((new IsAbsoluteUrl())->validate($url));
    }

    public function testLongTld() {
        $url = 'http://foo.somenewlongtld';
        $this->assertTrue((new IsAbsoluteUrl())->validate($url));
    }

    public function testIdn() {
        $url = 'http://xn--i1atf.xn--p1ai/';
        $this->assertTrue((new IsAbsoluteUrl())->validate($url));
    }

    public function testMalformedIdn() {
        $url = 'http://--i1atf.--p1ai/';
        $this->assertFalse((new IsAbsoluteUrl())->validate($url));
    }

}
