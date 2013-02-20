<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

/**
 * Scalar field
 * @package Carcass\Field
 */
class Scalar extends Base {

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        if (null !== $value && !is_scalar($value)) {
            $value = self::INVALID_VALUE;
        }
        parent::setValue($value);
        return $this;
    }

}
