<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

/**
 * Boolean field
 *
 * @package Carcass\Field
 */
class Boolean extends Base {

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        $this->value = (bool) $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function getValue() {
        return $this->value;
    }

}
