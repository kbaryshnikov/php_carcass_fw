<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Event;

use Carcass\Corelib;

trait DispatcherTrait {

    protected $event_handlers = [];

    public function removeEventHandlers($event_name) {
        unset($this->event_handlers[$event_name]);
        return $this;
    }

    public function addEventHandler($event_name, callable $callback, $once = false, $replace = false) {
        $handler_spec = [$this->convertCallableToIndex($callback), $callback, $once];
        if ($replace) {
            $this->event_handlers[$event_name] = [$handler_spec];
        } else {
            $this->removeEventHandler($event_name, $callback);
            $this->event_handlers[$event_name][] = $handler_spec;
        }
        return $this;
    }

    public function removeEventHandler($event_name, callable $callback) {
        if (!empty($this->event_handlers[$event_name])) {
            if (null !== $key = $this->findEventHandler($callback, $event_name)) {
                unset($this->event_handlers[$event_name][$key]);
            }
        }
        return $this;
    }

    public function fireEvent($event_name, array &$args = []) {
        if (!empty($this->event_handlers[$event_name])) {
            foreach ($this->event_handlers[$event_name] as $idx => $handler_spec) {
                $callback = $handler_spec[1];
                $once = $handler_spec[2];

                if (false === $callback($args)) {
                    break;
                }

                if ($once) {
                    unset($this->event_handlers[$event_name][$idx]);
                }
            }
        }
        return $this;
    }

    protected function findEventHandler(callable $callback, $event_name) {
        if (empty($this->event_handlers[$event_name])) {
            return null;
        }
        $callback_index = $this->convertCallableToIndex($callback);
        foreach ($this->event_handlers[$event_name] as $idx => $handler) {
            if ($handler[0] === $callback_index) {
                return $idx;
            }
        }
        return null;
    }

    protected static function convertCallableToIndex(callable $callable) {
        if (is_string($callable)) {
            $result = strtolower($callable);
        } elseif (is_array($callable)) {
            $scope = $callable[0];
            $fn = $callable[1];
            if (is_string($scope)) {
                $scope = strtolower($scope);
            } else {
                $scope = Corelib\ObjectTools::toString($scope);
            }
            $result = $scope . "::" . strtolower($fn);
        } else {
            $result = ":" . Corelib\ObjectTools::toString($callable);
        }
        return $result;
    }

}