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
 * Class SetHasValidChoice
 * @package Carcass\Rule
 */
class SetHasValidChoice extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_choice';

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    protected function validateFieldValue(Field\FieldInterface $Field) {
        if (!$Field instanceof Field\Multiselect) {
            return false;
        }
        return $this->validate(['selected_values' => $Field->getValue(), 'allowed_values' => $Field->getSetValues()]);
    }

    /**
     * @param $values
     * @return bool
     */
    public function validate($values) {
        extract($values);
        if (null === $selected_values) {
            return true;
        }
        if (empty($allowed_values)) {
            return false;
        }
        foreach ($selected_values as $selected) {
            if (!is_scalar($selected) || !isset($allowed_values[$selected])) {
                return false;
            }
        }

        return true;
    }
}
