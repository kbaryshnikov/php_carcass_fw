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
     * @param callable $append_message_fn
     * @throws \Exception|null
     * @return null
     */
    protected function develCollectExecutionTime($message, callable $fn, callable $append_message_fn = null) {
        $e = null;
        $result = null;

        if ($message instanceof \Closure) {
            $message = $message();
        }

        $Timer = $this->develStartTimer($message);
        try {
            $result = $fn();
        } catch (\Exception $e) {
            // pass
        }

        // finally:
        $Timer->stop();

        if ($append_message_fn) {
            $Timer->appendTitle($append_message_fn());
        }

        if ($e) {
            throw $e;
        }

        return $result;
    }

}