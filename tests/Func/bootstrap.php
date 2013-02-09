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

function init_app($run_mode = 'cli') {

    \Carcass\Application\Instance::destroy();
    return \Carcass\Application\Instance::init(__DIR__, [
        'env_data' => [
            'run_mode' => $run_mode,
            'configuration_name' => 'test',
        ],
    ]);

}
