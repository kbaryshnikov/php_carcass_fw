<?php

use \Carcass\Corelib;

class DatasourceTraitUser {
    use Corelib\DatasourceTrait;

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function &getDataArrayPtr() {
        return $this->data;
    }
}

class DatasourceRefTraitUser {
    use Corelib\DatasourceRefTrait;

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function &getDataArrayPtr() {
        return $this->data;
    }
}

class Corelib_DatasourceTraitTest extends PHPUnit_Framework_TestCase {

    public function testHas() {
        $array = ['a' => 1, 'b' => null, 'c' => false];
        $Datasource = new DatasourceTraitUser($array);
        $this->assertTrue($Datasource->has('a'));
        $this->assertTrue($Datasource->has('b'));
        $this->assertTrue($Datasource->has('c'));
        $this->assertFalse($Datasource->has('nope'));
    }

    public function testGet() {
        $array = ['a' => 1, 'b' => null, 'c' => false];
        $Datasource = new DatasourceTraitUser($array);
        $this->assertEquals(1, $Datasource->get('a'));
        $this->assertEquals(null, $Datasource->get('b'));
        $this->assertEquals(false, $Datasource->get('c'));
        $this->assertNull($Datasource->get('nope'));
        $this->assertFalse($Datasource->get('nope', false));
    }

    public function testGetObject() {
        $obj = new stdClass;
        $array = [Corelib\ObjectTools::toString($obj) => $obj];
        $Datasource = new DatasourceTraitUser($array);
        $this->assertSame($obj, $Datasource->get($obj));
    }

    public function testGetPath() {
        $Datasource = new DatasourceRefTraitUser([1, 'x' => 'y', 'a' => ['b' => ['c' => 'value']]]);
        $this->assertEquals('value', $Datasource->getPath('a.b.c'));
        $this->assertEquals('y', $Datasource->getPath('x'));
        $this->assertEquals(1, $Datasource->getPath('0'));
        $this->assertEquals(null, $Datasource->getPath('not.found'));
        $this->assertEquals(null, $Datasource->getPath('x.not.found'));
        $this->assertEquals(null, $Datasource->getPath('a.b.c.not.found'));
        $this->assertEquals(null, $Datasource->getPath('a.not.found'));
    }

    public function testGetRef() {
        $value = 1;
        $Datasource = new DatasourceRefTraitUser(['value' => &$value]);
        $ref = & $Datasource->getRef('value');
        $value++;
        $this->assertEquals($value, $ref);
    }

    public function test__get() {
        $array = ['a' => 1, 'b' => null, 'c' => false];
        $Datasource = new DatasourceTraitUser($array);
        $this->assertEquals(1, $Datasource->a);
        $this->assertEquals(null, $Datasource->b);
        $this->assertEquals(false, $Datasource->c);
        $this->setExpectedException('OutOfBoundsException');
        $Datasource->nope;
    }

}
