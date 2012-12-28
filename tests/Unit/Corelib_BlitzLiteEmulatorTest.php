<?php

use \Carcass\Corelib\BlitzLiteEmulator;

class Corelib_BlitzLiteEmulatorTest extends PHPUnit_Framework_TestCase {

    public function testSimpleVarsTpl() {
        $Tpl = new BlitzLiteEmulator;
        $Tpl->load('Hello {{ who }}{{footer.text}}');
        $Tpl->set(array(
            'who' => 'world',
            'footer' => array('text' => '!'),
        ));
        $result = $Tpl->parse();
        $this->assertEquals('Hello world!', $result);
    }

    public function testGlobalVars() {
        $Tpl = new BlitzLiteEmulator;
        $Tpl->load('{{ value }}{{ BEGIN items }}({{ name }}={{ value }}){{ END }}');
        $Tpl->set(array(
            'items' => array(
                array('name' => 'first'),
                array('name' => 'second', 'value' => 'local'),
            )
        ));
        $Tpl->setGlobals(array(
            'value' => 'global',
        ));
        $result = $Tpl->parse();
        $this->assertEquals('global(first=global)(second=local)', $result);
    }

    public function testIfUnlessTpl() {
        $Tpl = new BlitzLiteEmulator;
        $Tpl->load('Hello{{ UNLESS no_space }} {{ END }}{{ IF value }}{{ UNLESS foo }}{{ value }}{{ END }}{{ END }}{{ UNLESS test }}{{ IF value2.x }}{{ value2.x.y }}{{ END }}!{{ END }}');
        $Tpl->set(array(
            'value' => 'wor',
            'no_space' => false,
            'value2' => array('x' => array('y' => 'ld')),
        ));
        $result = $Tpl->parse();
        $this->assertEquals('Hello world!', $result);
    }

    public function testIterations() {
        $Tpl = new BlitzLiteEmulator;
        $tpl = 'Hello{{ BEGIN items }}{{ IF _first }}: {{ END }}{{value}}{{ BEGIN subitems }}{{ IF _first }}[{{ END }}{{ value }}{{ UNLESS _last }}, {{ END }}{{ IF _last }}]{{ END }}{{ END subitems }}{{ UNLESS _last }}; {{ END }}{{ END }}!';
        $Tpl->load($tpl);
        $set = array(
            'items' => array(
                array('value' => 1),
                array('value' => 2),
                array('value' => 3, 'subitems' => array(
                                        array('value' => '3.1'),
                                        array('value' => '3.2'),
                )),
                array('value' => 4, 'subitems' => array('value' => '4.1')),
            ),
        );
        $result = $Tpl->parse($set);
        $this->assertEquals('Hello: 1; 2; 3[3.1, 3.2]; 4[4.1]!', $result);
    }

    public function testCallbacks() {
        $Tpl = new BlitzLiteEmulatorChild;
        $tpl = 'Test: {{ test(1, "a", \'b\', foo, bar.x) }}{{ BEGIN bar }}[{{test(x)}}]{{ END }}';
        $Tpl->load($tpl);
        $result = $Tpl->parse(array(
            'foo' => true,
            'bar' => array('x' => 'val'),
        ));
        $this->assertEquals('Test: 1:a:b:1:val[val]', $result);
    }

    public function testClean() {
        $Tpl = new BlitzLiteEmulator;
        $Tpl->load('Hello {{ who }}{{footer.text}}');
        $Tpl->set(array(
            'who' => 'world',
            'footer' => array('text' => '!'),
        ));
        $Tpl->parse();
        $Tpl->clean();
        $result = $Tpl->parse();
        $this->assertEquals('Hello ', $result);
    }

    public function testCleanGlobals() {
        $Tpl = new BlitzLiteEmulator;
        $Tpl->load('Hello {{ global }}');
        $Tpl->setGlobals(array('global' => 'world'));
        $Tpl->parse();
        $Tpl->cleanGlobals();
        $this->assertEquals('Hello ', $Tpl->parse());
    }

}

class BlitzLiteEmulatorChild extends BlitzLiteEmulator {

    public function test() {
        $args = func_get_args();
        return join(':', $args);
    }

}
