<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * DataReceiverInterface trait implementation. Supports tainting and locks.
 *
 * <User> must implement:
 *
 * @method array getDataArrayPtr() must return reference to internal values storage
 * @method static DataReceiverInterface newSelf($value) must construct own subitem
 * @method static bool instanceOfSelf($value) must return whether $value is instance of <User>
 *
 * @package Carcass\Corelib
 */
trait DataReceiverTrait {

    /**
     * @var bool
     */
    protected $is_locked = false;
    /**
     * @var bool
     */
    protected $is_tainted = false;

    /**
     * @param \Traversable|array $data
     * @param bool $no_overwrite
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function import(/* Traversable */ $data, $no_overwrite = false) {
        if (!ArrayTools::isTraversable($data)) {
            throw new \InvalidArgumentException('Argument is not traversable');
        }
        foreach ($data as $key => $value) {
            if ($no_overwrite && array_key_exists($key, $this->getDataArrayPtr())) {
                continue;
            }
            if (ArrayTools::isTraversable($value) && !static::instanceOfSelf($value)) {
                $value = static::newSelf($value);
            }
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @param \Traversable $Source
     * @param bool $no_overwrite
     * @return $this
     */
    public function fetchFrom(\Traversable $Source, $no_overwrite = false) {
        foreach ($Source as $key => $value) {
            if ($no_overwrite && array_key_exists($key, $this->getDataArrayPtr())) {
                continue;
            }
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @param array $source
     * @param bool $no_overwrite
     * @return $this
     */
    public function fetchFromArray(array $source, $no_overwrite = false) {
        foreach ($source as $key => $value) {
            if ($no_overwrite && array_key_exists($key, $this->getDataArrayPtr())) {
                continue;
            }
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value) {
        $this->changeWith(function() use ($key, $value) {
            return $this->doSet($key, $value);
        });
        return $this;
    }

    /**
     * @param mixed $key
     * @return $this
     */
    public function delete($key) {
        $this->changeWith(function() use ($key) {
            return $this->doUnset($key);
        });
        return $this;
    }

    /**
     * @return $this
     */
    public function taint() {
        $this->is_tainted = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function untaint() {
        $this->is_tainted = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTainted() {
        return $this->is_tainted;
    }

    /**
     * @return $this
     */
    public function lock() {
        $this->is_locked = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function unlock() {
        $this->is_locked = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked() {
        return $this->is_locked;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function __set($key, $value) {
        $this->set($key, $value);
    }

    /**
     * @param mixed $key
     */
    public function __unset($key) {
        $this->delete($key);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return bool
     */
    protected function doSet($key, $value) {
        if (null === $key) {
            $this->getDataArrayPtr()[] = $value;
        } else {
            $this->getDataArrayPtr()[$key] = $value;
        }
        return true;
    }

    /**
     * @param mixed $key
     * @return bool
     */
    protected function doUnset($key) {
        if (array_key_exists($key, $this->getDataArrayPtr())) {
            unset($this->getDataArrayPtr()[$key]);
            return true;
        }
        return false;
    }

    /**
     * @param callable $applier must return false if should not taint
     * @throws \LogicException
     */
    protected function changeWith(Callable $applier) {
        if ($this->is_locked) {
            throw new \LogicException("Data receiver is locked");
        }
        if (false !== $applier()) {
            $this->taint();
        }
    }

}
