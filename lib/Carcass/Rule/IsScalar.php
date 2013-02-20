<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsScalar
 * @package Carcass\Rule
 */
class IsScalar extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'is_not_scalar';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || is_scalar($value));
    }

}
