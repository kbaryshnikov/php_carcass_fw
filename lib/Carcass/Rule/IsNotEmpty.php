<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsNotEmpty
 * @package Carcass\Rule
 */
class IsNotEmpty extends IsEmpty {

    /**
     * @var string
     */
    protected $ERROR = 'is_empty';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return $value === null || !parent::validate($value);
    }
}
