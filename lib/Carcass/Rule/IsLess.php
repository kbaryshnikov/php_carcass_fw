<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsLess
 * @package Carcass\Rule
 */
class IsLess extends Base {

    protected $min_value;

    /**
     * @var string
     */
    protected $ERROR = 'too_large';

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
        return (null === $value || $value < $this->min_value);
    }
}
