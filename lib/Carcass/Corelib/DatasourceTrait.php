<?php

namespace Carcass\Corelib;

trait DatasourceTrait {

    public function has($key) {
        return array_key_exists($key, $this->getDataArrayPtr());
    }

    public function get($key, $default_value = null) {
        self::prepareDatasourceKey($key);
        return $this->has($key) ? $this->getDataArrayPtr()[$key] : $default_value;
    }

    public function __get($key) {
        if (!$this->has($key)) {
            throw new \OutOfBoundsException("Missing value for '$key'");
        }
        return $this->get($key);
    }

    protected static function prepareDatasourceKey(&$key) {
        $key = is_object($key) ? ObjectTools::toString($key) : $key;
    }

}
