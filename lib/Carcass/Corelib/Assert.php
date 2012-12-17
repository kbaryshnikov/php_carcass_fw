<?php

namespace Carcass\Corelib;

class Assert {

    protected static $instance = null;

    private function __construct() {
        // pass
    }

    private function __clone() {
        // pass
    }

    public static function __callStatic($name, $arguments) {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return call_user_func_array([self::$instance, $name], $arguments);
    }

    public function __call($name, $arguments) {
        if (!$this->dispatch($name, $arguments)) {
            if ($this->exception_to_throw) {
                $exception_to_throw = $this->exception_to_throw;
                $this->exception_to_throw = null;
                throw new $exception_to_throw[1]($exception_to_throw[0]);
            } else {
                throw new AssertException("Assertion failed: '$name'");
            }
        }
        return $this;
    }

    protected function dispatch($name, $arguments) {
        if (!method_exists($this, "_$name")) {
            throw new BadMethodCallException("Unknown assertion: '$name'");
        }
        return call_user_func_array([$this, "_$name"], $arguments);
    }

    protected function _onFailureThrow($message, $exception_class = null) {
        $this->exception_to_throw = [(string)$message, (string)($exception_class ?: __NAMESPACE__ . '\\AssertException')];
        return $this;
    }

    protected function _not($assertion /* ... */) {
        $args = func_get_args();
        return !call_user_func_array([$this, 'dispatch'], $args);
    }

    protected function _isValidId($value, $strict = false) {
        return $this->_isInteger($value, $strict) && $value > 0;
    }

    protected function _isUnsignedInt($value, $strict = false) {
        return $this->_isInteger($value, $strict) && $value >= 0;
    }

    protected function _isInRange($value, $min, $max) {
        return $value >= $min && $value <= $max;
    }

    protected function _isInteger($value, $strict = false) {
        if (false == $strict) {
            return is_int($value) || ctype_digit(substr($value, 0, 1) == '-' ? substr($value, 1) : $value);
        } else {
            return is_int($value);
        }
    }

    protected function _is($value) {
        return true == $value;
    }

    protected function _isNot($value) {
        return false == $value;
    }

    protected function _isEmpty($value) {
        return empty($value);
    }

    protected function _isNotEmpty($value) {
        return !empty($value);
    }

    protected function _isScalar($value) {
        return is_scalar($value);
    }

    protected function _closure($value, Callable $Closure) {
        return $Closure($value);
    }

    protected function _matchesRegexp($value, $regexp) {
        return preg_match($regexp, $value);
    }

    protected $exception_to_throw = null;

}

class Assert_Exception extends \RuntimeException {
    // pass
}
