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
 * Class RuleInterface
 * @package Carcass\Rule
 */
interface RuleInterface {

    /**
     * @param \Carcass\Field\FieldInterface $Field
     * @return bool
     */
    public function validateField(Field\FieldInterface $Field);

    /**
     * @param $value
     * @return bool
     */
    public function validate($value);

    /**
     * @return string
     */
    public function getErrorName();

}
