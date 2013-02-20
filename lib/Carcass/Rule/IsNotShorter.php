<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsNotShorter
 * @package Carcass\Rule
 */
class IsNotShorter extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'is_too_short';
    protected $min_len;

    /**
     * @param $min_len
     */
    public function __construct($min_len) {
        $this->min_len = $min_len;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || mb_strlen($value) >= $this->min_len);
    }
}
