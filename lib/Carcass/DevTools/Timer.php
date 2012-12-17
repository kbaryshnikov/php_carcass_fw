<?php

namespace Carcass\DevTools;

use Carcass\Corelib as Corelib;

class Timer {

    protected
        $start_time = null,
        $elapsed = null,
        $title;

    public function __construct($title) {
        $this->title = $title;
    }

    public function start() {
        $this->start_time = Corelib\TimeTools::getMicrotime();
        $this->elapsed = null;
        return $this;
    }

    public function resume() {
        if ($this->start_time === null) {
            throw new LogicException('Timer has not been started');
        }
        if ($this->elapsed === null) {
            throw new LogicException('Timer has not been paused');
        }
        $this->start_time = Corelib\TimeTools::getMicrotime() - $this->elapsed;
    }

    public function pause() {
        if ($this->start_time === null) {
            throw new LogicException('Timer has not been started');
        }
        $this->elapsed = Corelib\TimeTools::getMicrotime() - $this->start_time;
        return $this;
    }

    public function isRunning() {
        return null === $this->start_time;
    }

    public function stop() {
        $this->pause();
        $this->start_time = null;
        return $this;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getValue() {
        return $this->elapsed;
    }

}
