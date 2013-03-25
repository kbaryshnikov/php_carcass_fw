<?php

use Carcass\Corelib;
use Carcass\Model;

use Carcass\Application\DI;

class TestCachedListItemBaseModel extends Model\Base {

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

class TestCachedListBaseModel extends Model\MemcachedList {

    protected static $cache_key = 'test{{ IF min_id }}{{ i(min_id) }}{{ END }}';

    public $chunk_size = null;

    public function getMct() {
        return $this->getQuery()->getMct();
    }

    /**
     * return $this
     */
    public function load($filter = []) {
        $this->getQuery()
            ->setListChunkSize($this->chunk_size)
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
        return '\TestCachedListItemBaseModel';
    }

}

class ModelMemcachedListTest extends PHPUnit_Framework_TestCase {

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

    public function testExceptionIsThrownIfLimitUndefined() {
        $this->fill();
        $Model = new TestCachedListBaseModel;

        $this->setExpectedException('\LogicException');
        $Model->load();
    }

    public function testLoadListWithLimit() {
        $this->fill();
        $Model = new TestCachedListBaseModel;

        $Mc = $Model->getMct()->getConnection();
        $Mc->flush();

        $Model->setLimit(3)->load();

        $this->assertEquals(6, $Mc->get('|test|#')['d']);
        $this->assertEquals($Model->exportArray(), array_values($Mc->get('|test|0')['d']));

        $this->assertEquals(3, count($Model));
        $this->assertEquals(6, $Model->getCount());

        /** @var $Item \TestCachedListItemBaseModel */
        foreach ($Model as $idx => $Item) {
            $this->assertInstanceOf('\TestCachedListItemBaseModel', $Item);
            $expected_id    = $this->stub_values[$idx]['id'];
            $expected_email = $this->stub_values[$idx]['email'];
            $this->assertEquals($expected_id, $Item->id);
            $this->assertEquals($expected_email, $Item->email);
        }
        $this->assertEquals(3, $Item->id);

        $this->Db->executeQuery('drop table if exists t');
        $Model = new TestCachedListBaseModel;

        $Mc = $Model->getMct()->getConnection();

        $Model->setLimit(3)->load();

        $this->assertEquals(6, $Mc->get('|test|#')['d']);
        $this->assertEquals($Model->exportArray(), array_values($Mc->get('|test|0')['d']));
    }

    public function testLoadListWithOffset() {
        $this->fill();
        $Model = new TestCachedListBaseModel;

        $Mc = $Model->getMct()->getConnection();
        $Mc->flush();

        $Model->setLimit(1)->setOffset(1)->load();

        $this->assertEquals(6, $Mc->get('|test|#')['d']);
        $this->assertEquals($Model->exportArray(), array_values($Mc->get('|test|0')['d']));

        $this->assertEquals(1, count($Model));
        $this->assertEquals(6, $Model->getCount());

        $Item = $Model[0];
        $idx  = 1;

        $this->assertInstanceOf('\TestCachedListItemBaseModel', $Item);
        $expected_id    = $this->stub_values[$idx]['id'];
        $expected_email = $this->stub_values[$idx]['email'];
        $this->assertEquals($expected_id, $Item->id);
        $this->assertEquals($expected_email, $Item->email);

        $this->Db->executeQuery('drop table if exists t');
        $Model = new TestCachedListBaseModel;

        $Mc = $Model->getMct()->getConnection();

        $Model->setLimit(1)->setOffset(1)->load();

        $this->assertEquals(6, $Mc->get('|test|#')['d']);
        $this->assertEquals(array_values($Model->exportArray()), array_values($Mc->get('|test|0')['d']));
    }

    public function testLoadListWithIntersection() {
        $this->fill();
        $Model = new TestCachedListBaseModel;
        $Model->chunk_size = 2;

        $Mc = $Model->getMct()->getConnection();
        $Mc->flush();

        $Model->setLimit(3)->load(['min_id'=>2]);

        $this->assertEquals(5, $Mc->get('|test2|#')['d']);
        $this->assertEquals(2, count($Mc->get('|test2|0')['d']));
        $this->assertEquals(1, count($Mc->get('|test2|2')['d']));

        $this->assertEquals(5, $Model->getCount(), 'where condition ignored for getCount');
        $this->assertEquals(3, count($Model));

        $this->Db->executeQuery("update t set email='new@value.com' where id = 5");

        $Model->setLimit(4)->load(['min_id'=>2]);
        $this->assertEquals(5, $Mc->get('|test2|#')['d']);

        $data0 = $Mc->get('|test2|0')['d'];
        $data2 = $Mc->get('|test2|2')['d'];

        $this->assertEquals(2, count($data0));
        $this->assertEquals(2, count($data2));

        $this->assertEquals($this->stub_values[1]['email'], $data0[0]['email']);
        $this->assertEquals('new@value.com', $data2[3]['email']);
    }

    public function testExportArray() {
        $this->fill();
        $Model = new TestCachedListBaseModel;
        $Model->setLimit(count($this->stub_values))->load();
        $array = $Model->exportArray();
        $this->assertEquals($this->stub_values, $array);
    }

    public function testRenderWithPaginator() {
        $this->fill();
        $Model = new TestCachedListBaseModel;
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
        $Model = new TestCachedListBaseModel;
        $Model->setLimit(2, 1)->load();

        $Result = new \Carcass\Corelib\Result;
        $Model->withoutPaginator()->renderTo($Result);

        $result = $Result->exportArray();
        $this->assertEquals($this->stub_values[1], $result[0]);
        $this->assertEquals($this->stub_values[2], $result[1]);
        $this->assertArrayNotHasKey('list', $Result->exportArray());
    }

}