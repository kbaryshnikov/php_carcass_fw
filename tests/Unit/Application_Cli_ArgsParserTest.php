<?php

use \Carcass\Application;

class Application_Cli_ArgsParserTest extends PHPUnit_Framework_TestCase {
    
    public function testArgsParser() {
        $result = Application\Cli_ArgsParser::parse([
            '-b',
            '-bool',
            '-str=value',
            '-arr=1',
            '-arr=2',
            'value1',
            'value2',
            '--',
            '-value3',
        ]);
        $this->assertTrue($result['b']);
        $this->assertTrue($result['bool']);
        $this->assertEquals('value', $result['str']);
        $this->assertEquals([1, 2], $result['arr']);
        $this->assertEquals('value1', $result[0]);
        $this->assertEquals('value2', $result[1]);
        $this->assertEquals('-value3', $result[2]);
    }

}
