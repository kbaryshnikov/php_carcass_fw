<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class LengthEquals
 * @package Carcass\Rule
 */
class LengthEquals extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_length';
    protected $required_len;

    /**
     * @param $required_len
     */
    public function __construct($required_len) {
        $this->required_len = $required_len;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        if (null === $value) {
            return true;
        }
        $len = is_scalar($value) ? mb_strlen($value) : count($value);
        return $len == $this->required_len;
    }
}
