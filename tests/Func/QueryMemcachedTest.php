<?php

use Carcass\Query;

use Carcass\Application\DI;

class QueryMemcachedTest extends PHPUnit_Framework_TestCase {

    protected $Db, $Mc;

    public function setUp() {
        init_app();
        $this->Db = DI::getConnectionManager()->getConnection(DI::getConfigReader()->getPath('application.connections.database'));
        $this->Mc = DI::getConnectionManager()->getConnection(DI::getConfigReader()->getPath('application.connections.memcached'));
        $this->Mc->flush();
    }

    public function testQueryFetchRow() {
        $Query = new Query\Memcached;
        $Query->setTags(['tag_id_{{ i(id) }}']);
        $cached = $this->Mc->get('id_1');
        $this->assertFalse($cached);
        $result = $Query->useCache('id_{{ i(id) }}')
            ->fetchRow('select 1 as id')
            ->execute(['id' => 1])
            ->getLastResult();
        $this->assertEquals(1, $result['id']);

        $this->assertEquals(['id' => 1], $this->Mc->get('id_1')['d']);
    }

    public function testQueryFetchRowDoesNotTouchDatabaseOnCacheHit() {
        $this->testQueryFetchRow();
        $Query = new Query\Memcached;
        $Query->setTags(['tag_id_{{ i(id) }}']);
        $result = $Query->useCache('id_{{ i(id) }}')
            ->fetchRow('select 2 as id')
            ->execute(['id' => 1])
            ->getLastResult();
        $this->assertEquals(1, $result['id']);
    }

    public function testQueryFetchAll() {
        $Query = new Query\Memcached;
        $Query->setTags(['tag_id_{{ i(id) }}']);
        $cached = $this->Mc->get('id_1');
        $this->assertFalse($cached);
        $result = $Query->useCache('id_{{ i(id) }}')
            ->fetchAll('select 1 as x, 2 as y union select 3, 4')
            ->execute(['id' => 1])
            ->getLastResult();
        $this->assertEquals(1, $result[0]['x']);
        $this->assertEquals(2, $result[0]['y']);
        $this->assertEquals(3, $result[1]['x']);
        $this->assertEquals(4, $result[1]['y']);
        $this->assertEquals([
            ['x' => 1, 'y' => 2],
            ['x' => 3, 'y' => 4],
        ], $this->Mc->get('id_1')['d']);
    }

    public function testQueryFetchWithCallback() {
        $Query = new Query\Memcached;
        $Query->setTags(['tag_id_{{ i(id) }}']);
        $result = $Query->useCache('id_{{ i(id) }}')
            ->fetchWith(function($Db, array $args) {
                return [1, $Db->getCell('SELECT 2')];
            })->execute(['id' => 1])->getLastResult();
        $this->assertEquals([1, 2], $result);
        $this->assertEquals([1, 2], $this->Mc->get('id_1')['d']);
    }

    public function testQueryInsertSelectUpdateDelete() {
        $Query = new Query\Memcached;
        $Query->setTags(['tag_id_{{ i(id) }}']);
        $Query->useCache('id_{{ i(id) }}');

        $this->Db->executeQuery('drop table if exists t');
        $this->Db->executeQuery('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');

        $this->Mc->set('&tag_id_1', 1);
        $this->Mc->set('id_1', 1);

        $id = $Query->insert('insert into t (s) values ({{ s(s) }})', ['s' => 'foo'], 'id');

        $this->assertEquals(1, $id);
        $this->assertFalse($this->Mc->get('&tag_id_1'));
        $this->assertFalse($this->Mc->get('id_1'));

        $row = $Query->fetchRow('select id, s from t where id = {{ i(id) }}')->execute(['id' => 1])->getLastResult();
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('foo', $row['s']);

        $cached = $this->Mc->get('id_1');
        $this->assertEquals(1, $cached['d']['id']);
        $this->assertEquals('foo', $cached['d']['s']);

        $affected_rows = $Query->modify('update t set s = {{ s(s) }} where id = {{ i(id) }}', ['s' => 'bar', 'id' => 1]);
        $this->assertEquals(1, $affected_rows);
        $this->assertFalse($Query->getMct()->get('id_{{ i(id) }}', ['id' => 1]));

        $row = $Query->fetchRow('select id, s from t where id = {{ i(id) }}')->execute(['id' => 1])->getLastResult();
        $this->assertEquals('bar', $row['s']);
        $this->assertEquals('bar', $Query->getMct()->get('id_{{ i(id) }}', ['id' => 1])['s']);

        $affected_rows = $Query->modify('delete from t where id = {{ i(id) }}', ['id' => 1]);
        $this->assertEquals(1, $affected_rows);
        $this->assertFalse($Query->getMct()->get('id_{{ i(id) }}', ['id' => 1]));
    }

}
