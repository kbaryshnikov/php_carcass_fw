<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsFloat
 * @package Carcass\Rule
 */
class IsFloat extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'is_not_float';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        if (null === $value) {
            return true;
        }
        return is_numeric($value);
    }

}
