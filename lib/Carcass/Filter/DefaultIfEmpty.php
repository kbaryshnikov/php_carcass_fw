<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

/**
 * DefaultIfEmpty filter: if value is empty, set its value to defined default value
 * @package Carcass\Filter
 */
class DefaultIfEmpty implements FilterInterface {

    /**
     * @var null
     */
    protected $default_value = null;

    /**
     * @param null $default_value
     */
    public function __construct($default_value = null) {
        $this->default_value = $default_value;
    }

    /**
     * @param mixed $value
     */
    public function filter(&$value) {
        if (empty($value)) {
            $value = $this->default_value;
        }
    }

}
