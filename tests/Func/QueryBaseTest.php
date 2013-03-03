<?php

use Carcass\Query;

class QueryBaseTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        init_app();
    }

    public function testQueryFetchRow() {
        $Query = new Query\Base;
        $result = $Query->fetchRow('select 1 as id')->execute()->getLastResult();
        $this->assertEquals(1, $result['id']);
    }

    public function testQueryFetchAll() {
        $Query = new Query\Base;
        $result = $Query->fetchAll('select 1 as id union select 2 as id')->execute()->getLastResult();
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[1]['id']);

        $result = $Query->fetchAll('select 1 as id, \'foo\' as s union select 2 as id, \'bar\' as s', ['id' => 1])
            ->execute()->getLastResult();
        $this->assertEquals('foo', $result[1]['s']);
        $this->assertEquals('bar', $result[2]['s']);

        $result = $Query->fetchAll('select 1 as id, \'foo\' as s union select 1 as id, \'bar\' as s', ['id' => INF])
            ->execute()->getLastResult();
        $this->assertEquals('foo', $result[1][0]['s']);
        $this->assertEquals('bar', $result[1][1]['s']);
    }

    public function testQueryFetchWithCallback() {
        $Query = new Query\Base;
        $result = $Query->fetchWith(function($Db, array $args) {
            return [ $Db->getCell('select ' . $args[0]), $result[1] = $Db->getCell('select ' . $args[1]) ];
        })->execute([1, 2])->getLastResult();
        $this->assertEquals([1, 2], $result);
    }

    public function testQueryUID() {
        $Query = new Query\Base;
        $Query->modify('drop table if exists t');
        $Query->modify('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');
        $id = $Query->insert('insert into t (s) values ({{ s(s) }})', ['s' => 'foo']);
        $this->assertEquals(1, $id);
        $affected_rows = $Query->modify('update t set s = {{ s(s) }} where id = {{ i(id) }}', ['s' => 'bar', 'id' => 1]);
        $this->assertEquals(1, $affected_rows);
        $affected_rows = $Query->modify('delete from t');
        $this->assertEquals(1, $affected_rows);
    }

    public function testQueryWithBeforeCall() {
        $Query = new Query\Base;
        $Query->modify('drop table if exists t');
        $Query->modify('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');
        $id = $Query
            ->before(function(Query\Base $Query) {
                $Query->insert('insert into t (s) values ({{ s(s) }})', ['s' => 'bar']);
            })
            ->insert('insert into t (s) values ({{ s(s) }})', ['s' => 'foo']);
        $this->assertEquals(2, $id);
        $data = $Query->fetchCol('select s from t order by id')->execute()->getLastResult();
        $this->assertEquals('bar', $data[0]);
        $this->assertEquals('foo', $data[1]);
    }

    public function testQueryWithAfterCall() {
        $Query = new Query\Base;
        $Query->modify('drop table if exists t');
        $Query->modify('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');
        $id = $Query
            ->after(function(Query\Base $Query) {
                $Query->insert('insert into t (s) values ({{ s(s) }})', ['s' => 'bar']);
            })
            ->insert('insert into t (s) values ({{ s(s) }})', ['s' => 'foo']);
        $this->assertEquals(2, $id);
        $data = $Query->fetchCol('select s from t order by id')->execute()->getLastResult();
        $this->assertEquals('foo', $data[0]);
        $this->assertEquals('bar', $data[1]);
    }

    public function testQueryUIDCallback() {
        $Query = new Query\Base;
        $Query->modify('drop table if exists t');
        $Query->modify('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');
        $id = $Query->insertWith(function($Db, $args) {
            $Db->query('insert into t (s) values ({{ s(s) }})', $args);
            $Db->query('insert into t (s) values ({{ s(s) }})', $args);
            return $Db->getLastInsertId();
        }, ['s' => 'foo']);
        $this->assertEquals(2, $id);
        $affected_rows = $Query->modifyWith(function($Db, $args) {
            $Db->query('update t set s = {{ s(s) }} where id = {{ i(id) }}', $args);
            return $Db->getAffectedRows();
        }, ['id' => 2, 's' => 'bar']);
        $this->assertEquals(1, $affected_rows);
    }

    public function testQuerySendTo() {
        $Query = new Query\Base;
        $Query->modify('drop table if exists t');
        $Query->modify('create table t (id int auto_increment, s varchar(255), primary key(id)) engine=innodb');
        $Query->modify('insert into t (s) values (\'foo\'), (\'bar\')');

        $Result = new \Carcass\Corelib\Hash;
        $Query->fetchRow('select id, s from t order by id limit 1')->execute()->sendTo($Result);
        $this->assertEquals('foo', $Result->s);

        $Result = new \Carcass\Corelib\Hash;
        $Query->fetchAll('select id, s from t order by id')->execute()->sendTo($Result);
        $this->assertEquals('foo', $Result[0]->s);
        $this->assertEquals('bar', $Result[1]->s);
    }

}
