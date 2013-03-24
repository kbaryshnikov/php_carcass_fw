<?php

use Carcass\Corelib;
use Carcass\Model;

use Carcass\Application\DI;

class TestListItemBaseModel extends Model\Base {

    public static function getModelRules() {
        return [
            'id'    => ['isValidId'],
            'email' => ['isNotEmpty', 'isValidEmail']
        ];
    }

    public function isLoaded() {
        return null !== $this->id;
    }

}

class TestListBaseModel extends Model\ListBase {

    /**
     * return $this
     */
    public function load($filter = []) {
        $this->getQuery()
            ->fetchList(
                "SELECT
                    {{ IF COUNT }}
                        COUNT(id)
                    {{ END }}
                    {{ UNLESS COUNT }}
                        id, email
                    {{ END }}
                FROM
                    t
                WHERE
                    1 = 1
                    {{ IF min_id }}
                        AND id >= {{ i(min_id )}}
                    {{ END }}
                {{ UNLESS COUNT }}
                    ORDER BY id
                    {{ limit(limit, offset) }}
                {{ END }}"
            )
            ->execute($filter)
            ->sendListTo($this);
        return $this;
    }

    protected static function getItemModelClass() {
        return '\TestListItemBaseModel';
    }

}

class ModelBaseListTest extends PHPUnit_Framework_TestCase {

    /** @var \Carcass\Mysql\Connection */
    protected $Db;

    protected $stub_values = [];

    public function setUp() {
        init_app();

        /** @var $Db \Carcass\Mysql\Connection */
        $Db = DI::getConnectionManager()->getConnection(DI::getConfigReader()->getPath('application.connections.database'));
        $Db->executeQuery('drop table if exists t');
        $Db->executeQuery('create table t (id integer auto_increment, email varchar(255) not null, primary key(id)) engine=innodb');

        $this->Db = $Db;
    }

    protected function fill() {
        $tokens = [];
        $id     = 1;
        foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $letter) {
            $email               = $letter . '@domain.com';
            $this->stub_values[] = ['id' => $id++, 'email' => $email];
            $tokens[]            = "('$email')";
        }
        $values = join(',', $tokens);
        $this->Db->executeQuery("INSERT INTO t (email) VALUES $values");
    }

    public function testLoadList() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->load();

        $this->assertEquals(6, count($Model));

        /** @var $Item \TestListItemBaseModel */
        foreach ($Model as $idx => $Item) {
            $this->assertInstanceOf('\TestListItemBaseModel', $Item);
            $expected_id    = $this->stub_values[$idx]['id'];
            $expected_email = $this->stub_values[$idx]['email'];
            $this->assertEquals($expected_id, $Item->id);
            $this->assertEquals($expected_email, $Item->email);
        }
    }

    public function testLoadListWithLimit() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->setLimit(3)->load();

        $this->assertEquals(3, count($Model));
        $this->assertEquals(6, $Model->getCount());

        /** @var $Item \TestListItemBaseModel */
        foreach ($Model as $idx => $Item) {
            $this->assertInstanceOf('\TestListItemBaseModel', $Item);
            $expected_id    = $this->stub_values[$idx]['id'];
            $expected_email = $this->stub_values[$idx]['email'];
            $this->assertEquals($expected_id, $Item->id);
            $this->assertEquals($expected_email, $Item->email);
        }
        $this->assertEquals(3, $Item->id);
    }

    public function testLoadListWithOffset() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->setLimit(1)->setOffset(1)->load();

        $this->assertEquals(1, count($Model));
        $this->assertEquals(6, $Model->getCount());

        $Item = $Model[0];
        $idx  = 1;

        $this->assertInstanceOf('\TestListItemBaseModel', $Item);
        $expected_id    = $this->stub_values[$idx]['id'];
        $expected_email = $this->stub_values[$idx]['email'];
        $this->assertEquals($expected_id, $Item->id);
        $this->assertEquals($expected_email, $Item->email);
    }

    public function testLoadListWithCondition() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->setLimit(1)->load(['min_id' => 2]);

        $this->assertEquals(5, $Model->getCount(), 'where condition ignored for getCount');

        $this->assertEquals(1, count($Model));

        $Item = $Model[0];
        $idx  = 1;

        $this->assertInstanceOf('\TestListItemBaseModel', $Item);
        $expected_id    = $this->stub_values[$idx]['id'];
        $expected_email = $this->stub_values[$idx]['email'];
        $this->assertEquals($expected_id, $Item->id);
        $this->assertEquals($expected_email, $Item->email);
    }

    public function testExportArray() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->load();
        $array = $Model->exportArray();
        $this->assertEquals($this->stub_values, $array);
    }

    public function testRenderWithPaginator() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->setLimit(2, 1)->load();

        $Result = new \Carcass\Corelib\Result;
        $Model->withPaginator()->renderTo($Result);

        $result = $Result->exportArray();
        $this->assertEquals(6, $result['count']);
        $this->assertEquals(2, $result['limit']);
        $this->assertEquals(1, $result['offset']);
        $this->assertEquals(2, count($result['list']));
        $this->assertEquals($this->stub_values[1], $result['list'][0]);
        $this->assertEquals($this->stub_values[2], $result['list'][1]);
    }

    public function testRenderWithoutPaginator() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->setLimit(2, 1)->load();

        $Result = new \Carcass\Corelib\Result;
        $Model->withoutPaginator()->renderTo($Result);

        $result = $Result->exportArray();
        $this->assertEquals($this->stub_values[1], $result[0]);
        $this->assertEquals($this->stub_values[2], $result[1]);
        $this->assertArrayNotHasKey('list', $Result->exportArray());
    }

}
