<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass;
use \Carcass\Corelib;

class DataReceiverTraitUser {
    use Corelib\DataReceiverTrait;

    public $data;
    
    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function &getDataArrayPtr() {
        return $this->data;
    }
}

class Corelib_DataReceiverTraitTest extends PHPUnit_Framework_TestCase {

    public function testFetchFromArray() {
        $array = [ 'a' => 1, 'b' => null, 'c' => false ];
        $DataReceiver = (new DataReceiverTraitUser)->fetchFromArray($array);
        $this->assertEquals($array, $DataReceiver->data);
        $DataReceiver->fetchFromArray($new_array = ['a' => 'new value']);
        $this->assertEquals($new_array + $array, $DataReceiver->data);
    }

    public function testFetchFrom() {
        $array = [ 'a' => 1, 'b' => null, 'c' => false ];
        $DataReceiver = (new DataReceiverTraitUser)->fetchFrom(new ArrayObject($array));
        $this->assertEquals($array, $DataReceiver->data);
        $DataReceiver->fetchFrom(new ArrayObject($new_array = ['a' => 'new value']));
        $this->assertEquals($new_array + $array, $DataReceiver->data);
    }

    public function testSet() {
        $DataReceiver = (new DataReceiverTraitUser)->set('a', 1);
        $this->assertEquals(1, $DataReceiver->data['a']);
    }

    public function testDelete() {
        $DataReceiver = (new DataReceiverTraitUser)->set('a', 1);
        $this->assertEquals(1, $DataReceiver->data['a']);
        $DataReceiver->delete('a');
        $this->assertArrayNotHasKey('a', $DataReceiver->data);
    }

    public function test__set() {
        $DataReceiver = new DataReceiverTraitUser;
        $DataReceiver->a = 1;
        $this->assertEquals(1, $DataReceiver->data['a']);
    }

    public function testTainting() {
        $DataReceiver = new DataReceiverTraitUser;
        $this->assertFalse($DataReceiver->isTainted());
        $DataReceiver->set('a', 1);
        $this->assertTrue($DataReceiver->isTainted());
        $DataReceiver->untaint();
        $this->assertFalse($DataReceiver->isTainted());
        $DataReceiver->taint();
        $this->assertTrue($DataReceiver->isTainted());
        $DataReceiver->untaint();
        $DataReceiver->b = 1;
        $this->assertTrue($DataReceiver->isTainted());
        $DataReceiver->untaint();
        $DataReceiver->fetchFrom(new ArrayObject([1]));
        $this->assertTrue($DataReceiver->isTainted());
        $DataReceiver->untaint();
        $DataReceiver->fetchFromArray([1, 2]);
        $this->assertTrue($DataReceiver->isTainted());
    }

    public function testLocking() {
        $DataReceiver = new DataReceiverTraitUser;
        $DataReceiver->set('a', 1);
        $this->assertFalse($DataReceiver->isLocked());
        $DataReceiver->lock();
        $this->assertTrue($DataReceiver->isLocked());
        try {
            $DataReceiver->set('a', 2);
        } catch (Exception $e) {}
        $this->assertInstanceOf('LogicException', $e);
        $this->assertEquals(1, $DataReceiver->data['a']);
        $DataReceiver->unlock();
        $DataReceiver->set('a', 2);
        $this->assertEquals(2, $DataReceiver->data['a']);
    }

}
