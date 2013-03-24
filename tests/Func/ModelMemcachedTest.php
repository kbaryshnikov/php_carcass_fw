<?php

use Carcass\Model;

use Carcass\Application\DI;

class TestMemcachedModel extends Model\Memcached {

    protected $id_key = 'id';

    protected static
        $cache_key = 'test_{{ i(id) }}',
        $cache_tags = [ 'Test_{{ i(id) }}' ];

    public static function getModelRules() {
        return [
            'id'        => [ 'isValidId' ],
            'email'     => [ 'isNotEmpty', 'isValidEmail' ]
        ];
    }

    public function getMct() {
        return $this->getQuery()->getMct();
    }

    public function isLoaded() {
        return null !== $this->id;
    }

    public function reload() {
        if (!$this->isLoaded()) {
            throw new \LogicException("Not loaded");
        }
        return $this->loadById($this->id);
    }

    public function loadById($id) {
        $this->doFetch('SELECT id, email FROM t WHERE id = {{ i(id) }}', compact('id'));
        return $this->isLoaded();
    }

    public function insert() {
        return $this->doInsert('INSERT INTO t SET email = {{ s(email) }}');
    }

    public function update() {
        return $this->doModify('UPDATE t SET email = {{ s(email) }} WHERE id = {{ i(id) }}');
    }

    public function delete() {
        return $this->doModify('DELETE FROM t WHERE id = {{ i(id) }}');
    }

}

class ModelMemcachedTest extends PHPUnit_Framework_TestCase {

    protected $Db;

    public function setUp() {
        init_app();
        $this->Db = DI::getConnectionManager()->getConnection(DI::getConfigReader()->getPath('application.connections.database'));
        $this->Db->executeQuery('drop table if exists t');
        $this->Db->executeQuery('create table t (id integer auto_increment, email varchar(255) not null, primary key(id)) engine=innodb');
    }

    public function testModel() {
        $M = new TestMemcachedModel;
        $MCT = $M->getMct();

        $cache_key = 'test_{{ i(id) }}';
        $cache_args = ['id' => 1];

        $MCT->flush($cache_args, [$cache_key]);
        $this->assertFalse($MCT->get($cache_key, $cache_args));

        $M->email = 'mail@test.com';
        $id = $M->insert();

        $this->assertEquals(1, $id);
        $this->assertFalse($MCT->get($cache_key, $cache_args));

        $this->assertTrue($M->loadById(1));
        $this->assertEquals(1, $M->id);
        $this->assertEquals('mail@test.com', $M->email);

        $this->assertEquals('mail@test.com', $MCT->get($cache_key, $cache_args)['email']);

        $M->email = 'new@test.com';
        $this->assertEquals(1, $M->update());

        $this->assertFalse($MCT->get($cache_key, $cache_args));

        $M->reload();
        $this->assertEquals('new@test.com', $M->email);
        $this->assertEquals('new@test.com', $MCT->get($cache_key, $cache_args)['email']);

        $this->assertEquals(1, $M->delete());
        $this->assertFalse($M->reload());
        $this->assertFalse($M->loadById(1));

        $this->assertEquals([], $MCT->get($cache_key, $cache_args));
    }

}
