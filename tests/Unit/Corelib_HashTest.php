<?php

use \Carcass\Corelib;
use \Carcass\Corelib\Hash;

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

        $Hash->import(['a' => 'overwritten', 'x' => 4], true);
        $this->assertEquals(1, $Hash->get('a'));
        $this->assertEquals(4, $Hash->get('x'));
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

    public function testImportArrayObjectAccess() {
        $Hash = new Hash;
        $Hash->import([
            ['a' => 1],
            ['a' => 2],
            'x' => 3
        ]);
        $this->assertEquals(3, $Hash['x']);
        $this->assertEquals(3, $Hash->x);
        $this->assertEquals(1, $Hash[0]->a);
        $this->assertEquals(1, $Hash[0]['a']);
        $this->assertEquals(2, $Hash->{1}['a']);
        $this->assertEquals(2, $Hash[1]->a);
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

    public function testExportArray() {
        $Hash = new Hash($array = ['x' => 1, 'y' => 2]);
        $this->assertEquals($array, $Hash->exportArray());
    }

    public function testRender() {
        $Hash = new Hash($array = ['x' => 1, 'y' => 2]);
        $Result = $this->getMock('\Carcass\Corelib\Result');
        $Result
            ->expects($this->once())
            ->method('assign')
            ->with($this->identicalTo($array));
        /** @noinspection PhpParamsInspection */
        $Hash->renderTo($Result);
    }

    public function testRenderFn() {
        $Hash = new Hash(['x' => 1, 'y' => 2]);
        $array = ['x' => 2, 'y' => 4];
        $Result = $this->getMock('\Carcass\Corelib\Result');
        $Result->expects($this->once())
            ->method('assign')
            ->with($this->identicalTo($array));
        $Hash->setRenderer(function($Result) {
            $Result->assign(array_map(function($value) { return $value * 2; }, $this->exportArray()));
        });
        /** @noinspection PhpParamsInspection */
        $Hash->renderTo($Result);
    }

}
