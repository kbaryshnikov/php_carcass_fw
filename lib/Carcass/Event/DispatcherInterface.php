<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Event;

/**
 * Event Dispatcher interface
 *
 * @package Carcass\Event
 */
interface DispatcherInterface {

    /**
     * @param $event_name
     * @param callable $callback
     * @param bool $once
     * @param bool $replace
     * @return $this
     */
    public function addEventHandler($event_name, callable $callback, $once = false, $replace = false);

    /**
     * @param $event_name
     * @param callable $callback (array &$args) => bool continue
     * @return $this
     */
    public function removeEventHandler($event_name, callable $callback);

    /**
     * @param $event_name
     * @return $this
     */
    public function removeEventHandlers($event_name);

    /**
     * @param $event_name
     * @param array $args
     * @return $this
     */
    public function fireEvent($event_name, array &$args = []);

}