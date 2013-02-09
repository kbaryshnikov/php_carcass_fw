<?php

namespace Carcass\Corelib;

trait DataReceiverTrait {

    protected $is_locked = false;
    protected $is_tainted = false;

    public function import(/* Traversable */ $data) {
        if (!ArrayTools::isTraversable($data)) {
            throw new \InvalidArgumentException('Argument is not traversable');
        }
        foreach ($data as $key => $value) {
            if (ArrayTools::isTraversable($value) && !static::instanceOfSelf($value)) {
                $value = static::newSelf($value);
            }
            $this->set($key, $value);
        }
        return $this;
    }

    public function fetchFrom(\Traversable $Source) {
        foreach ($Source as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }

    public function fetchFromArray(array $source) {
        foreach ($source as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }

    public function set($key, $value) {
        $this->changeWith(function() use ($key, $value) {
            return $this->doSet($key, $value);
        });
        return $this;
    }

    public function delete($key) {
        $this->changeWith(function() use ($key) {
            return $this->doUnset($key);
        });
        return $this;
    }

    public function taint() {
        $this->is_tainted = true;
        return $this;
    }

    public function untaint() {
        $this->is_tainted = false;
        return $this;
    }

    public function isTainted() {
        return $this->is_tainted;
    }

    public function lock() {
        $this->is_locked = true;
        return $this;
    }

    public function unlock() {
        $this->is_locked = false;
        return $this;
    }

    public function isLocked() {
        return $this->is_locked;
    }

    public function __set($key, $value) {
        $this->set($key, $value);
    }

    protected function doSet($key, $value) {
        if (null === $key) {
            $this->getDataArrayPtr()[] = $value;
        } else {
            $this->getDataArrayPtr()[$key] = $value;
        }
        return true;
    }

    protected function doUnset($key) {
        if (array_key_exists($key, $this->getDataArrayPtr())) {
            unset($this->getDataArrayPtr()[$key]);
            return true;
        }
        return false;
    }

    protected function changeWith(Callable $applier) {
        if ($this->is_locked) {
            throw new \LogicException("Data receiver is locked");
        }
        if (false !== $applier()) {
            $this->taint();
        }
    }

}
