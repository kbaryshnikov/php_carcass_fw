<?php

return [

    'sharding_database' => [
        'mysql_dsn' => 'mysql://test:test@localhost/TestSharding',
        'hs_dsn'    => 'hs://localhost/TestSharding',
    ],

    'shard_dbname_prefix' => 'TestShardDb',

    'server_defaults' => [
        'username' => 'test',
        'password' => 'test',
        'super_username' => 'root',
        'super_password' => '890p',
    ]

];
