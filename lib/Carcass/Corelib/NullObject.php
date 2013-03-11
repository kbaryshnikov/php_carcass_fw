<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * NullObject: "blackhole" implementation
 *
 * @package Carcass\Corelib
 */
class NullObject {

    /**
     * @param $key
     * @return $this
     */
    public function __get($key) {
        return $this;
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value) {
        // pass
    }

    /**
     * @param $method
     * @param $args
     * @return $this
     */
    public function __call($method, $args) {
        return $this;
    }

    /**
     * @param $method
     * @param $args
     * @return NullObject
     */
    public static function __callStatic($method, $args) {
        return new self;
    }

}
