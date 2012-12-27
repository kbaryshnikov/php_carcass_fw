<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Memcached\KeyBuilder;

class Memcached_KeyBuilder extends PHPUnit_Framework_TestCase {
    
    public function testScalars() {
        $this->assertEquals('test', KeyBuilder::parseString('test'));
        $this->assertEquals('test\\.test', KeyBuilder::parseString('test{{ s(s) }}', ['s' => '.test']));
        $this->assertEquals('1', KeyBuilder::parseString('{{ i(i) }}', ['i' => 1]));
        $this->assertEquals('1', KeyBuilder::parseString('{{ i(i) }}', ['i' => '1']));
        $this->assertEquals('-1', KeyBuilder::parseString('{{ i(i) }}', ['i' => -1]));
        $this->assertEquals('-1', KeyBuilder::parseString('{{ i(i) }}', ['i' => '-1']));
        $this->assertEquals('1.1', KeyBuilder::parseString('{{ f(f) }}', ['f' => 1.1]));
        $this->assertEquals('1.10', KeyBuilder::parseString('{{ n(n, 2) }}', ['n' => '1.1']));
        $this->assertEquals('1', KeyBuilder::parseString('{{ id(i) }}', ['i' => 1]));
        $this->assertEquals('1', KeyBuilder::parseString('{{ id(i) }}', ['i' => '1']));

        $this->assertEquals('test\\.test', KeyBuilder::parseString('test{{ sNul(s) }}', ['s' => '.test']));
        $this->assertEquals('1', KeyBuilder::parseString('{{ iNul(i) }}', ['i' => 1]));
        $this->assertEquals('1', KeyBuilder::parseString('{{ iNul(i) }}', ['i' => '1']));
        $this->assertEquals('-1', KeyBuilder::parseString('{{ iNul(i) }}', ['i' => -1]));
        $this->assertEquals('-1', KeyBuilder::parseString('{{ iNul(i) }}', ['i' => '-1']));
        $this->assertEquals('1.1', KeyBuilder::parseString('{{ fNul(f) }}', ['f' => 1.1]));
        $this->assertEquals('1.10', KeyBuilder::parseString('{{ nNul(n, 2) }}', ['n' => '1.1']));
        $this->assertEquals('1', KeyBuilder::parseString('{{ idNul(i) }}', ['i' => 1]));
        $this->assertEquals('1', KeyBuilder::parseString('{{ idNul(i) }}', ['i' => '1']));

        $this->assertEquals('NULL NULL NULL NULL NULL', KeyBuilder::parseString('{{ snul(a) }} {{ inul(a) }} {{ nnul(a, 1) }} {{ idnul(a) }} {{ fnul(a) }}'));
        $this->assertEquals('NULL NULL NULL NULL NULL', KeyBuilder::parseString('{{ snul(a) }} {{ inul(a) }} {{ nnul(a, 1) }} {{ idnul(a) }} {{ fnul(a) }}', ['a'=>null]));
    }

    public function testSet() {
        $this->assertEquals('a\.b;c;d\;', KeyBuilder::parseString('{{ set(set) }}', ['set' => ['a.b', 'c', 'd;']]));
        $this->assertEquals('a\.b;c;d\;', KeyBuilder::parseString('{{ setNul(set) }}', ['set' => ['a.b', 'c', 'd;']]));
        $this->assertEquals('NULL', KeyBuilder::parseString('{{ setNul(set) }}'));
        $this->assertEquals('NULL', KeyBuilder::parseString('{{ setNul(set) }}', ['set' => null]));
    }

    public function testJson() {
        $data = ["a", "b", "c" => "d"];
        $this->assertEquals(json_encode($data), KeyBuilder::parseString('{{ json(data) }}', compact('data')));
        $this->assertEquals(json_encode($data), KeyBuilder::parseString('{{ jsonNul(data) }}', compact('data')));
        $this->assertEquals('NULL', KeyBuilder::parseString('{{ jsonNul(data) }}'));
        $this->assertEquals('NULL', KeyBuilder::parseString('{{ jsonNul(data) }}', ['data' => null]));
    }

}
