<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Filter;

use Carcass\Corelib;

/**
 * Filter Factory
 * @package Carcass\Filter
 */
class Factory {

    /**
     * @param array $args (type [, constructor args...])
     * @return FilterInterface
     * @throws \InvalidArgumentException
     */
    public static function assemble(array $args) {
        $type = (string)array_shift($args);
        if (!$type) {
            throw new \InvalidArgumentException("Missing filter type");
        }
        $class = substr($type, 0, 1) == '\\' ? $type : __NAMESPACE__ . '\\' . ucfirst($type);
        return Corelib\ObjectTools::construct($class, $args);
    }

}
