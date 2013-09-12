<?php

use Carcass\Corelib\Hash;

class Bug6_CorelibHash_WrongExportFilteredHashSize_Test extends PHPUnit_Framework_TestCase {

    public function testMemcachedConnectionCreatesNewKeyBuilderWhenKeyChanges() {
        $Hash = new Hash(['x' => 1, 'y' => 2, 3 => 3, 'z' => null, 4 => 40, 5 => 'fifty']);
        $Filtered = $Hash->exportFilteredHash(['y', 'z']);
        $this->assertInstanceOf('\Carcass\Corelib\Hash', $Filtered);
        $this->assertEquals(2, count($Filtered));
        $this->assertEquals(['y' => 2, 'z' => null], $Filtered->exportArray());
    }

}
