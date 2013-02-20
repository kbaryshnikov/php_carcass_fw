<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * @package Carcass\Corelib
 */
class UniqueId {

    /**
     * Generates a unique ID
     *
     * @param string $prefix
     * @return string
     */
    public static function generate($prefix = '') {
        return uniqid($prefix, true);
    }

}
