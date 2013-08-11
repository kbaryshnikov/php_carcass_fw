<?php

use \Carcass\Postgresql;

class Postgresql_ClientTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $conn = test_postgresql_get_connection();
        pg_query($conn, "drop table if exists test");
        pg_query($conn, "
            create table test (
                id serial,
                s varchar(255),
                PRIMARY KEY(id)
            )
        ");
    }

    public function testQueryTemplate() {
        $Dsn = new Carcass\Connection\Dsn(test_postgresql_get_dsn());
        /** @noinspection PhpParamsInspection */
        $Client = new Postgresql\Client(Postgresql\Connection::constructWithDsn($Dsn));
        $Client->executeQueryTemplate("INSERT INTO test (s) VALUES ({{ s(s) }})", ['s' => 'foo']);
        $Client->executeQueryTemplate("INSERT INTO test (s) VALUES ({{ snul(s) }})", []);
        $Client->executeQueryTemplate("UPDATE test SET s = {{ s(s) }} WHERE id = {{ id(i) }}", ['i' => 1, 's' => '1']);
        $Client->executeQueryTemplate("UPDATE test SET s = {{ s(s) }} WHERE s is null", ['s' => 'second\'value']);
        $Client->executeQueryTemplate("INSERT INTO test (s) VALUES ({{ s(s) }})", ['s' => 'third value']);
        $Client->executeQueryTemplate("SELECT * FROM test ORDER BY id LIMIT {{ lim(limit) }}", ['limit' => 2]);
        $row = $Client->fetch();
        $this->assertEquals(['id' => '1', 's' => '1'], $row);
        $row = $Client->fetch();
        $this->assertEquals(['id' => '2', 's' => 'second\'value'], $row);
    }

    public function testPostgresqlDatabaseQueries() {
        $Dsn = new Carcass\Connection\Dsn(test_postgresql_get_dsn());
        /** @noinspection PhpParamsInspection */
        $Client = new Postgresql\Client(Postgresql\Connection::constructWithDsn($Dsn));
        $Client->query("INSERT INTO test (s) VALUES {{ BEGIN values }} ( {{ s }} ) {{ UNLESS _last }} , {{ END }} {{ END }}", ['values' => [
            ['s' => '1'],
            ['s' => '2'],
            ['s' => '3'],
        ]]);

        $expected = [
            ['id'=>'1','s'=>'1'],
            ['id'=>'2','s'=>'2'],
        ];
        $result = $Client->getAll("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id", [ 'id' => 2 ]);
        $this->assertEquals($expected, $result);

        $expected = [
            '1' => ['id'=>'1','s'=>'1'],
            '2' => ['id'=>'2','s'=>'2'],
        ];
        $result = $Client->getAll("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id", [ 'id' => 2 ], [ 'id' => 1 ]);
        $this->assertEquals($expected, $result);

        $expected = [
            '1' => [['id'=>'1','s'=>'1']],
            '2' => [['id'=>'2','s'=>'2']],
        ];
        $result = $Client->getAll("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id", [ 'id' => 2 ], [ 'id' => INF ]);
        $this->assertEquals($expected, $result);

        $expected = [
            '1' => [['1' => ['id'=>'1','s'=>'1']]],
            '2' => [['2' => ['id'=>'2','s'=>'2']]],
        ];
        $result = $Client->getAll("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id", [ 'id' => 2 ], [ 'id' => INF, 's' => 1 ]);
        $this->assertEquals($expected, $result);

        $expected = ['id'=>'1','s'=>'1'];
        $result = $Client->getRow("SELECT * FROM test WHERE id <= {{ id(id) }} ORDER BY id LIMIT {{ lim(limit) }}", [ 'id' => 2, 'limit' => 1 ]);
        $this->assertEquals($expected, $result);

        $expected = ['1', '2', '3'];
        $result = $Client->getCol("SELECT s FROM test ORDER BY id", []);
        $this->assertEquals($expected, $result);

        $expected = ['1', '2', '3'];
        $result = $Client->getCol("SELECT s FROM test ORDER BY id", [], 's');
        $this->assertEquals($expected, $result);

        $expected = ['1' => '1', '2' => '2', '3' => '3'];
        $result = $Client->getCol("SELECT id, s FROM test ORDER BY id", [], 'id', 's');
        $this->assertEquals($expected, $result);

        $result = $Client->getCell("SELECT s FROM test WHERE id = {{ id(id) }}", ['id' => 1]);
        $this->assertEquals('1', $result);
    }

    public function testUserDefinedQueryParser() {
        $Dsn = new Carcass\Connection\Dsn(test_postgresql_get_dsn());

        $QueryParser = $this->getMock('\Carcass\Postgresql\QueryParser');
        $QueryParser->expects($this->once())
            ->method('getTemplate')
            ->with($this->identicalTo('{{ test() }}'))
            ->will($this->returnCallback(function() use ($QueryParser) {
                $QueryTemplate = $this->getMockBuilder('\Carcass\Postgresql\QueryTemplate')->disableOriginalConstructor()->getMock();
                $QueryTemplate->expects($this->once())
                    ->method('parse')
                    ->will($this->returnValue('SELECT 123'));
                return $QueryTemplate;
            }));

        /** @noinspection PhpParamsInspection */
        $Client = new Postgresql\Client(Postgresql\Connection::constructWithDsn($Dsn), $QueryParser);
        $result = $Client->getCell('{{ test() }}');
        $this->assertEquals('123', $result);
    }

}
