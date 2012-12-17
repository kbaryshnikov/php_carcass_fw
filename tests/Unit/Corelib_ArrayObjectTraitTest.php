<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass;
use \Carcass\Corelib as Corelib;

class ArrayObjectTraitUser implements Iterator, ArrayAccess, Countable {
    use Corelib\ArrayObjectTrait;

    public $data;
    
    public function __construct(array $data = []) {
        $this->data = $data;
        reset($this->data);
    }

    public function &getDataArrayPtr() {
        return $this->data;
    }
}

class Corelib_ArrayObjectTraitTest extends PHPUnit_Framework_TestCase {

    public function testIterator() {
        $array = [1, 2, 3, 'x' => 4];
        $ArrayObject = new ArrayObjectTraitUser($array);
        $result = [];
        foreach ($ArrayObject as $key => $value) {
            $result[$key] = $value;
        }
        $this->assertEquals($array, $result);
    }

    public function testCount() {
        $this->assertEquals(0, count(new ArrayObjectTraitUser));
        $this->assertEquals(2, count(new ArrayObjectTraitUser([1,2])));
    }

    public function testArrayAccessGet() {
        $array = [1, 2, 3, 'x' => 4];
        $ArrayObject = new ArrayObjectTraitUser($array);
        foreach ($array as $k => $v) {
            $this->assertEquals($v, $ArrayObject[$k]);
            $this->assertTrue(isset($ArrayObject[$k]));
        }
        $this->assertFalse(isset($ArrayObject['nope']));
        $this->setExpectedException('OutOfBoundsException');
        $ArrayObject['nope'];
    }

    public function testArrayAccessSet() {
        $array = [1, 2, 3, 'x' => 4];
        $ArrayObject = new ArrayObjectTraitUser;
        $ArrayObject[] = 1;
        $ArrayObject[] = 2;
        $ArrayObject[] = 3;
        $ArrayObject['x'] = 4;
        $this->assertEquals($array, $ArrayObject->data);
    }

}
