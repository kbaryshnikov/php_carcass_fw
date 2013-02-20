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
 * Class EnumHasValidChoice
 * @package Carcass\Rule
 */
class EnumHasValidChoice extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_choice';

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate(['value' => $Field->getValue(), 'allowed_values' => $Field->getEnumValues()]);
    }

    /**
     * @param array $values
     * @return bool
     */
    public function validate($values) {
        if (!is_array($values) || !isset($values['value'], $values['allowed_values'])) {
            return false;
        }

        $value = $values['value'];
        $allowed_values = $values['allowed_values'];

        if (null === $value) {
            return true;
        }

        if (empty($allowed_values) || !array_key_exists($value, $allowed_values)) {
            return false;
        }

        return true;
    }
}
