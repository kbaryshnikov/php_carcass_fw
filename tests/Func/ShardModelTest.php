<?php

use Carcass\Model;

use Carcass\Application\Injector;

use Carcass\Shard;

class TestShardModel extends Shard\Model {

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
        return $this->doInsert('INSERT INTO t SET email = {{ s(email) }}', [], 'id');
    }

    public function update() {
        return $this->doModify('UPDATE t SET email = {{ s(email) }} WHERE id = {{ i(id) }}');
    }

    public function delete() {
        return $this->doModify('DELETE FROM t WHERE id = {{ i(id) }}');
    }

}

class ShardModelTest extends PHPUnit_Framework_TestCase {

    protected
        $Factory;

    public function setUp() {
        init_app();
        $this->Factory = new Shard\Factory;
    }

    public function testShardAllocator() {
    }

}
