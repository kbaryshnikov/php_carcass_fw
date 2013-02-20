<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsNotMore
 * @package Carcass\Rule
 */
class IsNotMore extends Base {

    protected $max_value;

    /**
     * @var string
     */
    protected $ERROR = 'too_large';

    /**
     * @param $max_value
     */
    public function __construct($max_value) {
        $this->max_value = $max_value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || $value <= $this->max_value);
    }
}
