<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

use Carcass\Field;

/**
 * Class DateIsInRange
 * @package Carcass\Rule
 */
class DateIsInRange extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'date_not_in_range';
    /**
     * @var int|null
     */
    protected $date_min = null;
    /**
     * @var int|null
     */
    protected $date_max = null;

    /**
     * @param $min
     * @param $max
     */
    public function __construct($min = null, $max = null) {
        isset($min) and $this->date_min = is_integer($min) ? $min : strtotime($min);
        isset($max) and $this->date_max = is_integer($max) ? $max : strtotime($max);
    }

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    protected function validateFieldValue(Field\FieldInterface $Field) {
        if (!$Field instanceof Field\Date) {
            return false;
        }
        return $this->validate([
            'value' => $Field->getValue(),
            'min_value' => $Field->getMinValue(),
            'max_value' => $Field->getMaxValue()
        ]);
    }

    /**
     * @param array $v
     * @return bool
     */
    public function validate($v) {
        if (!is_array($v) || null === $v['value'] || $v['value'] === Field\Base::INVALID_VALUE) {
            return false;
        }

        $min = $this->date_min === null ? $v['min_value'] : $this->date_min;
        $max = $this->date_max === null ? $v['max_value'] : $this->date_max;

        if ((null !== $min && $v['value'] < $min) || (null !== $max && $v['value'] > $max)) {
            return false;
        }

        return true;
    }
}
