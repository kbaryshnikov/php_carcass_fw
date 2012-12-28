<?php

use \Carcass\Corelib;

class Corelib_StringToolsTest extends PHPUnit_Framework_TestCase {
    
    public function testSplit() {
        $this->assertEquals(["a", "b"], Corelib\StringTools::split("a:b", ':'));
        $this->assertEquals(["a", "b"], Corelib\StringTools::split("a:b", ':', 2));
        $this->assertEquals(["a", "b"], Corelib\StringTools::split("a:b", ':', [1,2]));
        $this->assertEquals(["a:b:c"], Corelib\StringTools::split("a:b:c", ':', 1));
        $this->assertEquals(["a", "b:c"], Corelib\StringTools::split("a:b:c", ':', [1,2]));
        $this->assertEquals(["a", "b", "c"], Corelib\StringTools::split("a:b", ':', [null, null, "c"]));
        $this->assertEquals(["a", "b", "c"], Corelib\StringTools::split("a:b", ':', 3, [null, null, "c"]));
        $this->assertEquals(["a", "b:c", "d"], Corelib\StringTools::split("a:b:c", ':', 2, [null, null, "d"]));
    }

}
