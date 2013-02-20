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
        $type = gettype($value);
        switch ($type) {
            case 'resource':
                $contents = print_r($value, true);
                break;
            case 'array':
            case 'object':
                $type = null;
                // no break intentionally
            default:
                $contents = print_r($value, true);
        }
        return ($type === null ? '' : "$type: ") . $contents;
    }

}
