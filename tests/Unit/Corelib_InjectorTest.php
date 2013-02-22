<?php

use \Carcass\Corelib;
use \Carcass\Corelib\Injector;

class Corelib_InjectorTest extends PHPUnit_Framework_TestCase {
    
    public function testObjectDependency() {
        $inj = new Injector;
        $inj->dep = function() {
            return new stdClass;
        };
        $this->assertInstanceOf('stdClass', $inj->dep);
    }

    public function testValueDependency() {
        $inj = new Injector;
        $inj->dep = 'test';
        $this->assertEquals('test', $inj->dep);
    }

    public function testInstancesAreDifferent() {
        $inj = new Injector;
        $inj->dep = function() {
            return new stdClass;
        };
        $this->assertNotSame($inj->dep, $inj->dep);
    }

    public function testReusedInstancesAreSame() {
        $inj = new Injector;
        $inj->dep = $inj->reuse(function() {
            return new stdClass;
        });
        $this->assertSame($inj->dep, $inj->dep);
    }

    public function testUsingArguments() {
        $inj = new Injector;
        $inj->flag = 1;
        $inj->dependency = function() {
            return new Dependency;
        };
        $inj->service = function($inj) {
            return new Service($inj->dependency, $inj->flag);
        };
        $service = $inj->service;
        $this->assertInstanceOf('Service', $service);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertInstanceOf('Dependency', $service->dependency);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $service->flag);
    }

    public function testUsingCallArguments() {
        $inj = new Injector;
        $inj->dependency = function() {
            return new Dependency;
        };
        $inj->service = function($inj, $flag) {
            return new Service($inj->dependency, $flag);
        };
        $service = $inj->service(1);
        $this->assertInstanceOf('Service', $service);
        $this->assertInstanceOf('Dependency', $service->dependency);
        $this->assertEquals(1, $service->flag);
    }

    public function testClosureDependency() {
        $inj = new Injector;
        $closure = function() { return true; };
        $inj->setClosure('closure', $closure);
        $this->assertInstanceOf('Closure', $inj->closure);
        $this->assertTrue($inj->closure->__invoke());
    }

}

class Dependency {}
class Service {
    public function __construct(Dependency $dependency, $flag) {
        $this->dependency = $dependency;
        $this->flag = $flag;
    }
}
