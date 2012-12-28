<?php

use \Carcass\Memcached;

class Memcached_KeyTest extends PHPUnit_Framework_TestCase {

    public function testBuild() {
        $Key = Memcached\Key::create('foo_{{ snul(s) }}_{{ inul(i) }}');
        $this->assertEquals('foo_x_1', $Key(['s'=>'x', 'i'=>1]));
        $this->assertEquals('foo_xx_11', $Key(['s'=>'xx', 'i'=>11]));
        $this->assertEquals('foo_NULL_NULL', $Key());
    }

    public function testSetPrefix() {
        $Key = Memcached\Key::create('{{ snul(s) }}_{{ inul(i) }}');
        $Key('setPrefix', 'foo_');
        $this->assertEquals('foo_x_1', $Key(['s'=>'x', 'i'=>1]));
        $Key('setPrefix', '{{ i(i) }}_foo_');
        $this->assertEquals('{{ i(i) }}_foo_x_1', $Key(['s'=>'x', 'i'=>1]));
    }

    public function testSetSuffix() {
        $Key = Memcached\Key::create('{{ snul(s) }}_{{ inul(i) }}');
        $Key('setSuffix', '_foo');
        $this->assertEquals('x_1_foo', $Key(['s'=>'x', 'i'=>1]));
        $Key('setSuffix', '_foo_{{ i(i) }}');
        $this->assertEquals('x_1_foo_{{ i(i) }}', $Key(['s'=>'x', 'i'=>1]));
    }

    public function testBuildWithOpts() {
        $Key = Memcached\Key::create('{{ s(s) }}');
        $this->assertEquals('prefix_s', $Key(['s' => 's'], ['prefix' => 'prefix_']));
        $this->assertEquals('s_suffix', $Key(['s' => 's'], ['suffix' => '_suffix']));
    }

}
