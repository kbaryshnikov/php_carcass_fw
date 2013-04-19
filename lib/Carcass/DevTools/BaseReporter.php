<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\DevTools;

/**
 * BaseReporter
 * @package Carcass\DevTools
 */
abstract class BaseReporter {

    /**
     * @param mixed $value
     * @return $this
     */
    abstract public function dump($value);

    /**
     * @param \Exception $Exception
     * @return $this
     */
    abstract public function dumpException(\Exception $Exception);

    /**
     * @param mixed $value
     * @return string
     */
    protected function formatValue($value) {
        if (is_object($value) || is_array($value)) {
            // using var_dump to avoid recursion
            ob_start();
            var_dump($value);
            return ob_get_clean();
        }
        return var_export($value, true);
    }

}
