<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsValidDate
 * @package Carcass\Rule
 */
class IsValidDate extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_date';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return ($value === null || (is_int($value) || ctype_digit($value)));
    }

}
