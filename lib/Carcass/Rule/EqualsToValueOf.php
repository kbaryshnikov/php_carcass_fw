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
 * Class EqualsToValueOf
 * @package Carcass\Rule
 */
class EqualsToValueOf extends Base {

    /**
     * @var \Carcass\Field\FieldInterface
     */
    protected $OtherField;

    /**
     * @var string
     */
    protected $ERROR = 'values_do_not_match';

    /**
     * @param \Carcass\Field\FieldInterface $OtherField
     */
    public function __construct(Field\FieldInterface $OtherField) {
        $this->OtherField = $OtherField;
    }

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    protected function validateFieldValue(Field\FieldInterface $Field) {
        return $this->validate(['value' => $Field->getValue(), 'expected' => $this->OtherField->getValue()]);
    }

    /**
     * @param $values
     * @return bool
     */
    public function validate($values) {
        return $values['value'] === $values['expected'];
    }
}
