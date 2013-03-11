<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\DevTools;

use Carcass\Application\DI;
use Carcass\Corelib;

/**
 * @package Carcass\DevTools
 */
trait TimerTrait {

    /**
     * @return string
     */
    protected function develGetTimerGroup() {
        return 'common';
    }

    /**
     * @param $message
     * @return string
     */
    protected function develGetTimerMessage($message) {
        return $message;
    }

    /**
     * @param $message
     * @return Timer
     */
    protected function develStartTimer($message) {
        if (!DI::getDebugger()->isEnabled()) {
            return new Corelib\NullObject;
        }
        return DI::getDebugger()->createTimer($this->develGetTimerGroup(), $this->develGetTimerMessage($message))->start();
    }

    /**
     * @param $message
     * @param callable $fn
     * @return null
     * @throws \Exception
     */
    protected function develCollectExecutionTime($message, callable $fn) {
        $e = null;
        $result = null;

        $Timer = $this->develStartTimer($message);
        try {
            $result = $fn();
        } catch (\Exception $e) {
            // pass
        }

        // finally:
        $Timer->stop();

        if ($e) {
            throw $e;
        }

        return $result;
    }

}