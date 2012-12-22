<?php

namespace MyApp;

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Application as Application;
use \Carcass\Corelib as Corelib;

interface Foo {
}

class MyFoo implements Foo {
}

class TestTest extends \PHPUnit_Framework_TestCase {

    public function testFoo() {
        $Injector = new Corelib\Injector;
        $Injector->willUse('\\MyApp\\MyFoo');
        $Hash = $Injector->create('\\MyApp\\Foo');
        var_dump(get_class($Hash));
    }

}
