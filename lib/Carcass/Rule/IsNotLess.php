<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsNotLess
 * @package Carcass\Rule
 */
class IsNotLess extends Base {

    protected $min_value;

    /**
     * @var string
     */
    protected $ERROR = 'too_small';

    /**
     * @param $min_value
     */
    public function __construct($min_value) {
        $this->min_value = $min_value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || $value >= $this->min_value);
    }
}
