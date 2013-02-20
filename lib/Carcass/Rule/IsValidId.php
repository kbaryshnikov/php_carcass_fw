<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsValidId
 * @package Carcass\Rule
 */
class IsValidId extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'is_not_id';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        if (null === $value) {
            return true;
        }
        return ( is_int($value) || ctype_digit($value) ) && $value > 0;
    }
}
