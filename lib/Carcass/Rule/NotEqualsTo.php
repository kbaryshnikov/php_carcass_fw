<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class NotEqualsTo
 * @package Carcass\Rule
 */
class NotEqualsTo extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'forbidden_value';
    /**
     * @var array
     */
    protected $forbidden_values;

    /**
     * @param mixed|array $forbidden_values  value or array of values which are not acceptable
     */
    public function __construct($forbidden_values) {
        $this->forbidden_values = (array)$forbidden_values;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return (null === $value || !in_array($value, $this->forbidden_values, true));
    }

}
