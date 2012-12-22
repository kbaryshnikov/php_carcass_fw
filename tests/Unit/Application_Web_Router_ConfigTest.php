<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Application as Application;
use \Carcass\Config as Config;

class Application_Web_Router_ConfigTest extends PHPUnit_Framework_TestCase {

    private static $cfg = [
        '/' => 'Index',
        '/users/' => 'Users',
        '/users/{#id}' => 'Users.byId',
        '/users/{#id}-{$title}' => 'Users.byId',
        '/news/' => 'News',
        '/news/{$title}' => 'News.byTitle',
    ];

    public function testBaseRouting() {
        $Cfg = new Config\Item([
        ]);
    }

}
