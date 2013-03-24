<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * ArrayObject trait implementation. Implements Countable, ArrayAccess and Iterator interfaces.
 *
 * User must implement:
 * getDataArrayPtr() must return reference to internal values storage
 *
 * @package Carcass\Corelib
 */
trait ArrayObjectTrait {

    /**
     * @return array
     */
    protected function &getDataArrayPtr() {
        throw new \LogicException("Must be implemented by ArrayObjectTrait user");
        /** @noinspection PhpUnreachableStatementInspection */
        return [];
    }

    /**
     * @param mixed $key
     * @return bool
     */
    protected function hasArrayObjectItemByKey($key) {
        return array_key_exists($key, $this->getDataArrayPtr());
    }

    /**
     * @param mixed $key
     * @return mixed
     * @throws \OutOfBoundsException
     */
    protected function &getArrayObjectItemByKey($key) {
        if ($this->hasArrayObjectItemByKey($key)) {
            return $this->getDataArrayPtr()[$key];
        }
        throw new \OutOfBoundsException("Key is undefined: '$key'");
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    protected function setArrayObjectItemByKey($key, $value) {
        if ($key === null) {
            $this->getDataArrayPtr()[] = $value;
        } else {
            $this->getDataArrayPtr()[$key] = $value;
        }
    }

    /**
     * @param mixed $key
     */
    protected function unsetArrayObjectItemByKey($key) {
        unset($this->getDataArrayPtr()[$key]);
    }

    /**
     * @return int
     */
    public function count() {
        return count($this->getDataArrayPtr());
    }

    /**
     * @return mixed|null
     */
    public function current() {
        $key = $this->key();
        return $key === null ? null : $this->getArrayObjectItemByKey($key);
    }

    /**
     * @return mixed
     */
    public function key() {
        return key($this->getDataArrayPtr());
    }

    /**
     * @return mixed|null
     */
    public function next() {
        next($this->getDataArrayPtr());
        return $this->current();
    }

    /**
     * @return mixed|null
     */
    public function rewind() {
        reset($this->getDataArrayPtr());
        return $this->current();
    }

    /**
     * @return bool
     */
    public function valid() {
        return $this->key() !== null;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return $this->hasArrayObjectItemByKey($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function &offsetGet($offset) {
        if ($this->hasArrayObjectItemByKey($offset)) {
            return $this->getArrayObjectItemByKey($offset);
        }
        throw new \OutOfBoundsException("Offset is undefined: '$offset'");
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        $this->setArrayObjectItemByKey($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        $this->unsetArrayObjectItemByKey($offset);
    }

}
