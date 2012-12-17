<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass;
use \Carcass\Corelib as Corelib;
use \Carcass\Corelib\Hash as Hash;

class Corelib_HashTest extends PHPUnit_Framework_TestCase {

    public function testConstruction() {
        $Hash = new Hash(['a' => 1, 'b' => ['a' => 2, 'b' => ['c' => 3]]]);
        $this->assertEquals(1, $Hash->get('a'));
        $this->assertEquals(2, $Hash->get('b')->get('a'));
        $this->assertEquals(3, $Hash->get('b')->get('b')->get('c'));
    }

    public function testImport() {
        $Hash = (new Hash)->import(['a' => 1, 'b' => ['a' => 2, 'b' => ['c' => 3]]]);
        $this->assertEquals(1, $Hash->get('a'));
        $this->assertEquals(2, $Hash->get('b')->get('a'));
        $this->assertEquals(3, $Hash->get('b')->get('b')->get('c'));
    }

    public function testMerge() {
        $Hash = (new Hash)->merge(['a' => 1, 'b' => ['a' => 2]]);
        $this->assertEquals(1, $Hash->get('a'));
        $this->assertEquals(['a' => 2], $Hash->get('b'));
    }

    public function testArrayAccess() {
        $Hash = new Hash;
        $Hash['a'] = 1;
        $Hash[] = 2;
        $this->assertEquals(1, $Hash['a']);
        $this->assertEquals(2, $Hash[0]);
    }

    public function testArrayAccessModification() {
        $Hash = new Hash(['a' => 1]);
        $Hash['a']++;
        $Hash['b'] = $b = new stdClass;
        $this->assertEquals(2, $Hash['a']);
        $this->assertSame($b, $Hash['b']);
    }

    public function testArrayAccessOutOfBounds() {
        $this->setExpectedException('OutOfBoundsException');
        (new Hash)['nope'];
    }

    public function testIterator() {
        $array = [ 1, 2, 3, 'x' => 4 ];
        foreach (new Hash($array) as $key => $value) {
            $this->assertEquals($array[$key], $value);
        }
    }

    public function testObjectAccess() {
        $Hash = new Hash(['a' => 1, 'inner' => ['b' => 2], 'object' => $object = new stdClass]);
        $this->assertEquals(1, $Hash->a);
        $this->assertEquals(2, $Hash->inner->b);
        $this->assertSame($object, $Hash->object);
        $Hash->a++;
        $this->assertEquals(2, $Hash->a);
        $this->setExpectedException('OutOfBoundsException');
        $Hash->nope;
    }

    public function testClone() {
        $obj = new stdClass;
        $Subhash = new Hash(['x' => 1]);

        $Hash = new Hash(['a' => 1, 'obj' => $obj, 'sub' => $Subhash]);
        $Hash2 = clone $Hash;

        $Hash2->a++;
        $this->assertEquals(1, $Hash->get('a'));
        $this->assertEquals(2, $Hash2->get('a'));
        $this->assertNotSame($obj, $Hash2->get('obj'));
        $this->assertNotSame($Subhash, $Hash2->get('sub'));
        $Hash2->sub->x++;
        $this->assertEquals(2, $Hash2->sub->x);
    }

}
