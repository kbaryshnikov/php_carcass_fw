<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

/**
 * ID field
 * @package Carcass\Field
 */
class Id extends Base {

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        $this->value = number_format($value, 0, '', '');
        if ($this->value < 1) {
            $this->value = null;
        }
        return $this;
    }

}
