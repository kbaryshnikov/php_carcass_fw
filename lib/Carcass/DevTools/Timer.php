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
 * Timer
 * @package Carcass\DevTools
 */
class Timer {

    protected $start_time = null;
    protected $elapsed = null;
    protected $title;

    /**
     * @param string $title
     */
    public function __construct($title) {
        $this->title = (string)$title;
    }

    /**
     * @return $this
     */
    public function start() {
        $this->start_time = Corelib\TimeTools::getMicrotime();
        $this->elapsed = null;
        return $this;
    }

    /**
     * @throws \LogicException
     */
    public function resume() {
        if ($this->start_time === null) {
            throw new \LogicException('Timer has not been started');
        }
        if ($this->elapsed === null) {
            throw new \LogicException('Timer has not been paused');
        }
        $this->start_time = Corelib\TimeTools::getMicrotime() - $this->elapsed;
    }

    /**
     * @return $this
     * @throws \LogicException
     */
    public function pause() {
        if ($this->start_time === null) {
            throw new \LogicException('Timer has not been started');
        }
        $this->elapsed = Corelib\TimeTools::getMicrotime() - $this->start_time;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRunning() {
        return null === $this->start_time;
    }

    /**
     * @return $this
     */
    public function stop() {
        $this->pause();
        $this->start_time = null;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return null
     */
    public function getValue() {
        return $this->elapsed;
    }

}
