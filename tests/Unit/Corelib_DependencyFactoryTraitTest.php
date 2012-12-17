<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Corelib as Corelib;

class Test {
    use Corelib\DependencyFactoryTrait;

    public function __construct(array $dependency_classes) {
        $this->dependency_classes = $dependency_classes;
    }

    public function callAssembleDependency($name, array $args = []) {
        return $this->assembleDependency($name, $args);
    }
}

class TestA {
    public function __construct() {
        $this->ctor_args = func_get_args();
    }
}
class TestB extends TestA {}

class DependencyFactoryTraitTest extends PHPUnit_Framework_TestCase {
    
    public function testFactory() {
        $Container = new Test(['a' => 'TestA', 'b' => 'TestB', 'hash' => '\\Carcass\\Corelib\\Hash']);
        $A = $Container->callAssembleDependency('a');
        $this->assertInstanceOf('TestA', $A);
        $this->assertEquals([], $A->ctor_args);
        $B = $Container->callAssembleDependency('b', [1, 2, 3]);
        $this->assertInstanceOf('TestB', $B);
        $this->assertEquals([1, 2, 3], $B->ctor_args);
        $Hash = $Container->callAssembleDependency('hash', [ ['foo' => 'bar' ] ]);
        $this->assertInstanceOf('\\Carcass\\Corelib\\Hash', $Hash);
        $this->assertEquals('bar', $Hash->foo);
    }

}
