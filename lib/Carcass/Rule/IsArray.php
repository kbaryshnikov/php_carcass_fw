<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsArray
 * @package Carcass\Rule
 */
class IsArray extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'is_not_array';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || is_array($value));
    }
}
