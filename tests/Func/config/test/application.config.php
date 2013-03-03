<?php

return [

    'connections' => [
        'database' => 'mysql://test:test@localhost/test',
        'memcached' => 'memcached://127.0.0.1',
    ],

    'debugger' => [
//        'enable' => true,
        'reporter' => 'console:stderr',
    ],

];
