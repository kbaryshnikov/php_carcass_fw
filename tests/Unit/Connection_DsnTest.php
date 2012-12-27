<?php

require_once __DIR__ . '/carcass-test.php';

use \Carcass\Connection;
use \Carcass\Corelib;

class Connection_DsnTest extends PHPUnit_Framework_TestCase {
    
    public function testDsnParser() {
        $Dsn = new Connection\Dsn('mysql://localhost');
        $this->assertEquals('mysql', $Dsn->type);
        $this->assertEquals('localhost', $Dsn->hostname);
        $this->assertEquals('mysql://localhost/', (string)$Dsn);
        $this->assertEquals('mysql', $Dsn->getType());

        $Dsn = new Connection\Dsn('mysql://localhost:3306');
        $this->assertEquals(3306, $Dsn->port);
        $this->assertEquals('mysql://localhost:3306/', (string)$Dsn);

        $Dsn = new Connection\Dsn('mysql://user@localhost:3306');
        $this->assertEquals('user', $Dsn->user);
        $this->assertEquals('mysql://user@localhost:3306/', (string)$Dsn);

        $Dsn = new Connection\Dsn('mysql://user:pass@localhost:3306');
        $this->assertEquals('pass', $Dsn->password);
        $this->assertEquals('mysql://user:pass@localhost:3306/', (string)$Dsn);

        $Dsn = new Connection\Dsn('mysql://user@localhost:3306/name');
        $this->assertEquals('name', $Dsn->name);
        $this->assertEquals('mysql://user@localhost:3306/name', (string)$Dsn);

        $Dsn = new Connection\Dsn('mysql://localhost/?a=1&b=2');
        $this->assertEquals(['a'=>1, 'b'=>2], $Dsn->args->exportArray());
        $this->assertEquals('mysql://localhost/?a=1&b=2', (string)$Dsn);

        $Dsn = new Connection\Dsn('mysql://unix:/tmp/mysql.sock');
        $this->assertEquals('/tmp/mysql.sock', $Dsn->socket);
        $this->assertEquals('mysql://unix:/tmp/mysql.sock', (string)$Dsn);

        $Dsn = Connection\Dsn::factory('mysql://localhost/?a=1&b=2');
        $this->assertEquals('mysql://localhost/?a=1&b=2', (string)$Dsn);
    }

    public function testPool() {
        $Pool = Connection\Dsn::factory(['mysql://127.0.0.1', 'mysql://localhost']);
        $this->assertInstanceOf('\Carcass\Connection\DsnPool', $Pool);
        $this->assertEquals(2, count($Pool));
        $this->assertEquals('mysql', $Pool->getType());
        foreach ($Pool as $_) $this->assertInstanceOf('\Carcass\Connection\Dsn', $_);
        $this->assertEquals('127.0.0.1', $Pool[0]->hostname);
        $this->assertEquals('localhost', $Pool[1]->hostname);

        $Pool2 = Connection\Dsn::factory(['mysql://127.0.0.1/', 'mysql://localhost/']);
        $this->assertEquals( (string)$Pool, (string)$Pool2 );

        $this->setExpectedException('LogicException');
        $Pool = Connection\Dsn::factory(['mysql://127.0.0.1', 'pgsql://localhost']);
    }

}
