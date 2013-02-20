<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsNotLonger
 * @package Carcass\Rule
 */
class IsNotLonger extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'is_too_long';
    protected $max_len;

    /**
     * @param $max_len
     */
    public function __construct($max_len) {
        $this->max_len = $max_len;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || mb_strlen($value) <= $this->max_len);
    }

}
