<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class EqualsTo
 * @package Carcass\Rule
 */
class EqualsTo extends Base {

    protected
        $ERROR = 'wrong_value',
        $correct_values;

    /**
     * @param $correct_values
     */
    public function __construct($correct_values) {
        $this->correct_values = (array)$correct_values;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || in_array($value, $this->correct_values));
    }
}
