<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass;
use \Carcass\Corelib as Corelib;

class ExportableTraitUser implements Corelib\ExportableInterface {
    use Corelib\ExportableTrait;
    
    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function &getDataArrayPtr() {
        return $this->data;
    }
}

class Corelib_ExportableTraitTest extends PHPUnit_Framework_TestCase {

    public function testExportArray() {
        $array = [ 'a' => 1, 'b' => [ 'c' => [ new stdClass, [] ] ] ];
        $this->assertEquals($array, (new ExportableTraitUser($array))->exportArray());
        $this->assertEquals([], (new ExportableTraitUser)->exportArray());
    }

    public function testExportArrayRecursive() {
        $init   = [ 'a' => 1, 'b' => new ExportableTraitUser(['c' => 1]) ];
        $expect = [ 'a' => 1, 'b' => ['c' => 1] ];
        $export = (new ExportableTraitUser($init))->exportArray();
        $this->assertEquals($expect, $export);
    }

}
