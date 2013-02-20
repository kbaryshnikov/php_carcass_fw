<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class InArray
 * @package Carcass\Rule
 */
class InArray extends Base {

    /**
     * @var array
     */
    protected $known_values = [];
    /**
     * @var bool
     */
    protected $strict = false;

    /**
     * @var string
     */
    protected $ERROR = 'value_not_in_array';

    /**
     * @param array $known_values
     * @param bool $strict
     */
    public function __construct(array $known_values, $strict = false) {
        $this->known_values = $known_values;
        $this->strict = $strict;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return null === $value || in_array($value, $this->known_values, $this->strict);
    }
}
