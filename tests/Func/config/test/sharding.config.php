<?php

return [

    'connections' => [
        'central' => [
            'mysql'     => 'mysql://test:test@localhost/',
            'hs'        => 'hs://localhost/',
        ],
        'memcached_pool' => [
            'memcached://127.0.0.1/',
        ],
    ],

    'mappers' => [
        'mysql'     =>  function($Injector, $Config) {
                            return new \Carcass\Shard\DsnMapper_MysqlMap(
                                $Injector->ConnectionManager->getConnection(
                                    $Config->connections->central->hs
                                ),
                                [],
                                [ 'shard_database' => 'ShardingTest' ]
                            );
                        },
        'memcached' =>  function($Injector, $Config) {
                            return new \Carcass\Shard\DsnMapper_MemcachedPool(
                                $Config->connections->memcached_pool
                            );
                        },
    ],

    'allocators' => [
        'mysql'     =>  function($Injector, $Config) {
                            return new \Carcass\Shard\Allocator_MysqlMap(
                                $Injector->ConnectionManager->getConnection(
                                    $Config->connections->central->mysql
                                ),
                                'ShardingTest'
                            );
                        },
    ],

];
