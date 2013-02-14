<?php
set_include_path(get_include_path() . ':' . dirname(dirname(__DIR__)) . '/lib/');
spl_autoload_register(function($class) {
    $file_relative_path = ltrim(strtr($class, ['\\' => '/', '_' => '/'])) . '.php';
    if ($file_abs_path = stream_resolve_include_path($file_relative_path)) {
        require_once $file_abs_path;
        return true;
    }
    return false;
});

function test_mysql_get_dsn() {
    return 'mysql://test:test@localhost/test';
}

function test_hs_get_dsn() {
    return 'hs://localhost/test';
}

function test_mysql_get_connection() {
    return new mysqli('localhost', 'test', 'test', 'test');
}
