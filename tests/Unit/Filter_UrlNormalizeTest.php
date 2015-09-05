<?php

use \Carcass\Filter\UrlNormalize;

class Filter_UrlNormalizeTest extends PHPUnit_Framework_TestCase {

    public function testNormalizeUnescapePath() {
        $noUnescape = ['?', '#', '[', ']'];
        $unescape = [':', '/', '!', '$', '&', "'", '(', ')', '*', '+', ',', ';', '=', '~'];
        $url = 'http://domain.tld/' . join('', array_map('urlencode', $noUnescape)) . '-' . join('', array_map('urlencode', $unescape));
        (new UrlNormalize())->filter($url);
        $expected = 'http://domain.tld/' . join('', array_map('urlencode', $noUnescape)) . '-' . join('', $unescape);
        $this->assertEquals($expected, $url);
    }

    public function testNormalizeUnescapeWithQueryString() {
        $url = 'http://google.com/a' . urlencode('?') . 'b' . urlencode('&') . '?a=b+c';
        (new UrlNormalize())->filter($url);
        $expected = 'http://google.com/a' . urlencode('?') . 'b&?a=b' . urlencode('+') . 'c';
        $this->assertEquals($expected, $url);
    }

}
