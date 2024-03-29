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

    public function uppercaseEmail() {
        $this->email = strtoupper($this->email);
    }

    public function appendToEmail($s) {
        $this->email .= $s;
    }

}

class TestListBaseModel extends Model\ListBase {

    /**
     * return $this
     */
    public function load($filter = []) {
        $this->getQueryDispatcher()
            ->fetchList(
                "SELECT
                    {{ IF COUNT }}
                        COUNT(id)
                    {{ END }}
                    {{ UNLESS COUNT }}
                        id, email
                        {{ IF add_extra_field }}
                           , 1 as extra_field
                        {{ END }}
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
        $id = 1;
        foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $letter) {
            $email = $letter . '@domain.com';
            $this->stub_values[] = ['id' => $id++, 'email' => $email];
            $tokens[] = "('$email')";
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
            $expected_id = $this->stub_values[$idx]['id'];
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
            $expected_id = $this->stub_values[$idx]['id'];
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
        $idx = 1;

        $this->assertInstanceOf('\TestListItemBaseModel', $Item);
        $expected_id = $this->stub_values[$idx]['id'];
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
        $idx = 1;

        $this->assertInstanceOf('\TestListItemBaseModel', $Item);
        $expected_id = $this->stub_values[$idx]['id'];
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

    public function testForEachActions() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->setLimit(2)->load();
        $this->assertEquals('a@domain.com', $Model[0]->email);
        $Model->forEachItemDo('uppercaseEmail');
        $this->assertEquals('A@DOMAIN.COM', $Model[0]->email);
        $this->assertEquals('B@DOMAIN.COM', $Model[1]->email);
        $Model->setLimit(3)->load();
        $this->assertEquals('A@DOMAIN.COM', $Model[0]->email);
        $this->assertEquals('B@DOMAIN.COM', $Model[1]->email);
        $this->assertEquals('C@DOMAIN.COM', $Model[2]->email);
        $Model->forEachItemDo('appendToEmail', ['x']);
        $this->assertEquals('A@DOMAIN.COMx', $Model[0]->email);
        $this->assertEquals('B@DOMAIN.COMx', $Model[1]->email);
        $this->assertEquals('C@DOMAIN.COMx', $Model[2]->email);
        $Model->forEachItemDo('appendToEmail', ['y'], true);
        $Model->setLimit(3)->load();
        $this->assertEquals('a@domain.comy', $Model[0]->email);
        $this->assertEquals('b@domain.comy', $Model[1]->email);
        $this->assertEquals('c@domain.comy', $Model[2]->email);
        $Model->forEachItemDoClosure(
            function ($Item) {
                $Item->email = 'z' . $Item->email;
            },
            true
        );
        $Model->setLimit(4)->load();
        $this->assertEquals('za@domain.com', $Model[0]->email);
        $this->assertEquals('zb@domain.com', $Model[1]->email);
        $this->assertEquals('zc@domain.com', $Model[2]->email);
        $this->assertEquals('zd@domain.com', $Model[3]->email);
        $Model->forEachItemDoClosure(
            function ($Item) {
                $Item->email = 'x' . $Item->email;
            }
        );
        $this->assertEquals('xza@domain.com', $Model[0]->email);
        $this->assertEquals('xzb@domain.com', $Model[1]->email);
        $this->assertEquals('xzc@domain.com', $Model[2]->email);
        $this->assertEquals('xzd@domain.com', $Model[3]->email);
    }

    public function testItemModelFieldsetIsInitializedInDynamicModeFromListModel() {
        $this->fill();
        $Model = new TestListBaseModel;
        $Model->setLimit(2)->load(['add_extra_field' => true]);
        /** @var TestListItemBaseModel $Item */
        $Item = $Model[0];
        $this->assertEquals(1, $Item->extra_field);
    }

}
