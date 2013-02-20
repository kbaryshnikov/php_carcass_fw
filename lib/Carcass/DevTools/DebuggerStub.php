<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\DevTools;

use Carcass\Corelib;

/**
 * DebuggerStub
 * @package Carcass\DevTools
 */
class DebuggerStub extends Corelib\NullObject {

    public function isEnabled() {
        return false;
    }

    public function __toString() {
        return "";
    }

}
