<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsNumeric
 * @package Carcass\Rule
 */
class IsNumeric extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'is_not_numeric';
    /**
     * @var bool
     */
    protected $allow_negative = false;

    /**
     * @param bool $allow_negative
     */
    public function __construct($allow_negative = false) {
        $this->allow_negative = (bool)$allow_negative;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        if (null === $value) {
            return true;
        }
        if (is_int($value)) {
            return $this->allow_negative ? true : $value >= 0;
        }
        if (ctype_digit($value)) {
            return true;
        }   
        if ($this->allow_negative && substr($value, 0, 1) == '-' && ctype_digit(substr($value, 1))) {
            return true;
        }
        return false;
    }
}
