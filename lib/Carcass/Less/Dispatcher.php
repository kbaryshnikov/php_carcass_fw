<?php

namespace Carcass\Less;

use Carcass\Fs;

class Dispatcher {

    const
        LESS_CACHE_KEY = 'less_{{ s(file) }}';

    protected
        $Compiler = null,
        $Cacher = null,
        $less_path,
        $target_path = null;

    public function __construct($Cacher, $less_path, $target_path = null) {
        $this->Cacher = $Cacher;
        $this->setLessPath($less_path);
        $target_path and $this->setTargetPath($target_path);
    }

    public function setLessPath($less_path) {
        $this->less_path = rtrim($less_path, '/');
        return $this;
    }

    public function setTargetPath($target_path) {
        $this->target_path = rtrim($target_path, '/');
        return $this;
    }

    public function compile($file, &$mtime = null) {
        if (!$this->target_path) {
            throw new \LogicException("target path is undefined");
        }
        $target_file_name = '/' . md5($file) . '.css';
        $result = $this->compileFileToLessString($file, $mtime);
        if (!$result) {
            throw new \LogicException("Empty less compilation result");
        }
        Fs\Directory::mkdirIfNotExists($this->target_path);
        file_put_contents($this->target_path . $target_file_name, $result, LOCK_EX);
        return $target_file_name;
    }

    public function compileFileToLessString($file, &$mtime = null) {
        $file_name = $this->less_path . '/' . ($file = ltrim($file, '/'));
        $cache_key = $this->Cacher->key(self::LESS_CACHE_KEY, compact('file'));

        $less_cache = $this->Cacher->get($cache_key);
        if (!is_array($less_cache) || empty($less_cache)) {
            $less_cache = null;
        }

        $result = $this->getCompiler()->cachedCompile( $less_cache ?: $file_name );

        if (!$less_cache || $result['updated'] > $less_cache['updated']) {
            $this->Cacher->set($cache_key, $result);
        }

        $mtime = $result['updated'];

        return $result['compiled'];
    }

    protected function getCompiler() {
        if (null === $this->Compiler) {
            $this->Compiler = $this->assembleCompiler();
        }
        return $this->Compiler;
    }

    protected function assembleCompiler() {
        $Compiler = new Compiler;
        $Compiler->setImportDir($this->less_path);
        $Compiler->setFormatter('Compressed');
        return $Compiler;
    }

}
