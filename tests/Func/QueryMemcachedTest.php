<?php

use Carcass\Query;

use Carcass\Application\DI;

class QueryMemcachedTest extends PHPUnit_Framework_TestCase {

    /**
     * @var \Carcass\Mysql\Connection
     */
    protected $Db;
    /**
     * @var \Carcass\Memcached\Connection
     */
    protected $Mc;

    public function setUp() {
        init_app();
        $this->Db = DI::getConnectionManager()->getConnection(DI::getConfigReader()->getPath('application.connections.database'));

        /** @var $Mc \Carcass\Memcached\Connection */
        $Mc = DI::getConnectionManager()->getConnection(DI::getConfigReader()->getPath('application.connections.memcached'));
        $Mc->flush();
        $this->Mc = $Mc;
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
        $this->assertEquals(
            [
                ['x' => 1, 'y' => 2],
                ['x' => 3, 'y' => 4],
            ], $this->Mc->get('id_1')['d']
        );
    }

    public function testQueryFetchWithCallback() {
        $Query = new Query\Memcached;
        $Query->setTags(['tag_id_{{ i(id) }}']);
        $result = $Query->useCache('id_{{ i(id) }}')
            ->fetchWith(
                function (\Carcass\Mysql\Client $Db, array $args) {
                    return [1, $Db->getCell('SELECT 2')];
                }
            )->execute(['id' => 1])->getLastResult();
        $this->assertEquals([1, 2], $result);
        $this->assertEquals([1, 2], $this->Mc->get('id_1')['d']);
    }

    public function testQueryFetchUnchunkedList() {
        $this->Db->executeQuery('drop table if exists t');
        $this->Db->executeQuery('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');
        $this->Db->executeQuery(
            "insert into t (s) values " . join(
                ', ', array_reduce(
                    range(1, 20), function ($result, $item) {
                        $result[] = "('$item')";
                        return $result;
                    }, []
                )
            )
        );

        $Query = new Query\Memcached;
        $Query
            ->setTags(['tag'])
            ->useCache('items');

        $result = $Query
            ->fetchList(
                "SELECT
                    {{ IF COUNT }}
                        COUNT(id)
                    {{ END }}
                    {{ UNLESS COUNT }}
                        id, s
                    {{ END }}
                FROM
                    t
                {{ UNLESS COUNT }}
                    ORDER BY id
                    {{ limit(limit, offset) }}
                {{ END }}"
            )
            ->setLimit(15)
            ->execute()
            ->getLastResult();

        $count  = $Query->getLastCount();
        $this->assertEquals(20, $count);
        $this->assertEquals(15, count($result));

        $mc_key = $this->Mc->get('items');
        $this->assertEquals(20, $mc_key['d']['c']);
        $this->assertEquals($result, $mc_key['d']['d']);
    }

    public function testQueryFetchChunkedList() {
        $this->Db->executeQuery('drop table if exists t');
        $this->Db->executeQuery('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');
        $this->Db->executeQuery(
            "insert into t (s) values " . join(
                ', ', array_reduce(
                    range(1, 20), function ($result, $item) {
                        $result[] = "('$item')";
                        return $result;
                    }, []
                )
            )
        );

        $Query = new Query\Memcached;
        $Query
            ->setTags(['tag'])
            ->useCache('items')
            ->setListChunkSize(10);

        $result = $Query
            ->fetchList(
                "SELECT
                    {{ IF COUNT }}
                        COUNT(id)
                    {{ END }}
                    {{ UNLESS COUNT }}
                        id, s
                    {{ END }}
                FROM
                    t
                {{ UNLESS COUNT }}
                    ORDER BY id
                    {{ limit(limit, offset) }}
                {{ END }}"
            )
            ->setLimit(15)
            ->execute()
            ->getLastResult();

        $count  = $Query->getLastCount();
        $this->assertEquals(20, $count);
        $this->assertEquals(15, count($result));
        $this->assertEquals(['id' => '10', 's' => '10'], $result[9]);

        $mc_count_key = $this->Mc->get('|items|#');
        $this->assertEquals(20, $mc_count_key['d']);
        $this->assertNotEmpty($mc_count_key['t']['&tag']);
        $mc_data0 = $this->Mc->get('|items|0');
        $this->assertEquals(10, count($mc_data0['d']));
        $this->assertEquals($mc_count_key['t']['&tag'], $mc_data0['t']['&tag']);
        $mc_data10 = $this->Mc->get('|items|10');
        $this->assertEquals(5, count($mc_data10['d']));
        $this->assertEquals($mc_count_key['t']['&tag'], $mc_data10['t']['&tag']);

        $Query->setLimit(20)->execute();

        $mc_data10 = $this->Mc->get('|items|10');
        $this->assertEquals(10, count($mc_data10['d']));

        (new Query\Memcached)->setTags(['tag'])->modify("delete from t where id=1");

        $result = $Query->setLimit(20)->execute()->getLastResult();
        $this->assertEquals(2, $result[0]['id']);
    }

    public function testQueryInsertSelectUpdateDelete() {
        $Query = new Query\Memcached;
        $Query->setTags(['tag_id_{{ i(id) }}']);
        $Query->useCache('id_{{ i(id) }}');

        $this->Db->executeQuery('drop table if exists t');
        $this->Db->executeQuery('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');

        $this->Mc->set('&tag_id_1', 1);
        $this->Mc->set('id_1', 1);

        $id = $Query->setLastInsertIdFieldName('id')->insert('insert into t (s) values ({{ s(s) }})', ['s' => 'foo']);

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
