<?php

namespace Carcass\Corelib;

trait ArrayObjectTrait {

    protected function hasArrayObjectItemByKey($key) {
        return array_key_exists($key, $this->getDataArrayPtr());
    }

    protected function &getArrayObjectItemByKey($key) {
        if ($this->hasArrayObjectItemByKey($key)) {
            return $this->getDataArrayPtr()[$key];
        }
        throw new \OutOfBoundsException("Key is undefined: '$key'");
    }

    protected function setArrayObjectItemByKey($key, $value) {
        if ($key === null) {
            $this->getDataArrayPtr()[] = $value;
        } else {
            $this->getDataArrayPtr()[$key] = $value;
        }
    }

    protected function unsetArrayObjectItemByKey($key) {
        unset($this->getDataArrayPtr()[$key]);
    }

    public function count() {
        return count($this->getDataArrayPtr());
    }

    public function current() {
        $key = $this->key();
        return $key === null ? null : $this->getArrayObjectItemByKey($key);
    }

    public function key() {
        return key($this->getDataArrayPtr());
    }

    public function next() {
        next($this->getDataArrayPtr());
        return $this->current();
    }

    public function rewind() {
        reset($this->getDataArrayPtr());
        return $this->current();
    }

    public function valid() {
        return $this->key() !== null;
    }

    public function offsetExists($offset) {
        return $this->hasArrayObjectItemByKey($offset);
    }

    public function &offsetGet($offset) {
        if ($this->hasArrayObjectItemByKey($offset)) {
            return $this->getArrayObjectItemByKey($offset);
        }
        throw new \OutOfBoundsException("Offset is undefined: '$offset'");
    }

    public function offsetSet($offset, $value) {
        $this->setArrayObjectItemByKey($offset, $value);
    }

    public function offsetUnset($offset) {
        $this->unsetArrayObjectItemByKey($offst);
    }

}
