<?php

namespace Carcass\Application;

class Autoloader {

    protected $extension = '.php';

    protected $collector = [];

    public function __construct(array $lib_path = [], $extension = null) {
        $lib_path and set_include_path(join(':', array_unique(array_merge(explode(':', get_include_path()), $lib_path))));
        $extension and $this->extension = $extension;
        spl_autoload_register([$this, 'resolve']);
    }

    public function resolve($class_name) {
        $file_relative_path = ltrim(strtr($class_name, ['\\' => '/', '_' => '/']), '/') . $this->extension;
        if ($file_abs_path = stream_resolve_include_path($file_relative_path)) {
            require_once $this->collector[$class_name] = $file_abs_path;
            return true;
        }
        return false;
    }

    public function getAutoloadedFiles() {
        return $this->collector;
    }

}
