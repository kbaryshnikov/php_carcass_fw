<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Event;

use Carcass\Application\DI;

trait PublisherTrait {

    /**
     * @var DispatcherInterface
     */
    protected $EventDispatcher = null;

    public function fireEvent($event_name, array $args) {
        $this->getEventDispatcher()->fireEvent($event_name, $args);
    }

    protected function getEventDispatcher() {
        if (null === $this->EventDispatcher) {
            throw new \RuntimeException('No event dispatcher configured');
        }
        return $this->EventDispatcher;
    }

    protected function setEventDispatcher(DispatcherInterface $EventDispatcher) {
        $this->EventDispatcher = $EventDispatcher;
    }

}