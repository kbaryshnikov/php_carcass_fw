<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsEmpty
 * @package Carcass\Rule
 */
class IsEmpty extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'is_not_empty';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || false === $value || (is_array($value) && !count($value)) || (is_scalar($value) && !strlen($value)));
    }
}
