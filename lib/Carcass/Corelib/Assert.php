<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Assertions implementation.
 * Can be chained, subcalls are called on returned instance via fluent interface.
 * If an assertion has failed, throws AssertException (unless another classname given) with given text.
 *
 * Examples:
 *
 * @code
 * Assert::that('has valid user ID')->isValidId($user_id);
 * Assert::that('is integer and in range of 1..100', 'OutOfRangeException')->isInteger($value)->isInRange(1, 100);
 * @endcode
 *
 * @package Carcass\Corelib
 */
class Assert {

    /** @var string */
    protected $assert_that;

    /** @var null|string */
    protected $exception_class = null;

    /** @var bool */
    protected $negate_next = false;

    private function __construct($assert_that, $exception_class = null) {
        $this->assert_that = $assert_that;
        $this->exception_class = null === $exception_class ? null : strval($exception_class);
    }

    private function __clone() {
        // pass
    }

    /**
     * @param string $what
     * @param string|null $exception_class
     * @return Assert
     */
    public static function that($what, $exception_class = null) {
        return new static($what, $exception_class);
    }

    public function not() {
        $this->negate_next = true;
        return $this;
    }

    public function isValidId($value, $strict = false) {
        return $this->dispatch(function() use ($value, $strict) {
            return $this->_isInteger($value, $strict) && $value > 0;
        });
    }

    public function isUnsignedInt($value, $strict = false) {
        return $this->dispatch(function() use ($value, $strict) {
            return $this->_isInteger($value, $strict) && $value >= 0;
        });
    }

    public function isInRange($value, $min, $max) {
        return $this->dispatch(function() use ($value, $min, $max) {
            return $value >= $min && $value <= $max;
        });
    }

    public function isNumeric($value) {
        return $this->dispatch(function() use ($value) {
            return is_numeric($value);
        });
    }

    public function is($value, $equal_to = true) {
        return $this->dispatch(function() use ($value, $equal_to) {
            return $equal_to == $value;
        });
    }

    public function isNot($value) {
        return $this->dispatch(function() use ($value) {
            return !$value;
        });
    }

    public function isEmpty($value) {
        return $this->dispatch(function() use ($value) {
            return empty($value);
        });
    }

    public function isNotEmpty($value) {
        return $this->dispatch(function() use ($value) {
            return !empty($value);
        });
    }

    public function isScalar($value) {
        return $this->dispatch(function() use ($value) {
            return is_scalar($value);
        });
    }

    public function closure($value, Callable $Closure) {
        return $this->dispatch(function() use ($value, $Closure) {
            return $Closure($value);
        });
    }

    public function matchesRegexp($value, $regexp) {
        return $this->dispatch(function() use ($value, $regexp) {
            return preg_match($regexp, $value);
        });
    }

    public function isTraversable($value) {
        return $this->dispatch(function() use ($value) {
            return is_array($value) || $value instanceof \Traversable;
        });
    }

    public function isInteger($value, $strict = false) {
        return $this->dispatch(function() use ($value, $strict) {
            return $this->_isInteger($value, $strict);
        });
    }

    protected function _isInteger($value, $strict = false) {
        if ($strict) {
            return is_int($value);
        } else {
            return is_int($value) || (is_string($value) && ctype_digit(substr($value, 0, 1) == '-' ? substr($value, 1) : $value));
        }
    }

    protected function dispatch(Callable $fn) {
        $result = $fn();
        if ($this->negate_next) {
            $this->negate_next = false;
            $result = !$result;
        }
        if (!$result) {
            $exception_class = $this->exception_class ?: '\Carcass\Corelib\AssertException';
            throw new $exception_class("Assertion failed that {$this->assert_that}");
        }
        return $this;
    }

}

/**
 * AssertException is thrown when assertion has failed
 * @package Carcass\Corelib
 */
class AssertException extends \RuntimeException {
    // pass
}
