<?php

namespace Carcass\DevTools;

use Carcass\Application as Application;

class Debugger {

    const TRUNCATE_DEFAULT = 300;

    protected
        $truncate,
        $timers = [],
        $Reporter;

    public function __construct($Reporter = null) {
        if (null !== $Reporter) {
            $this->setReporter($Reporter);
        }
    }

    public function isEnabled() {
        return true;
    }

    public function setReporter($Reporter) {
        $this->Reporter = $Reporter;
        return $this;
    }

    public function dump($value, $header = null, $severity = null) {
        $this->Reporter->dump($header === null ? $value : [$header => $value], $severity);
        return $this;
    }

    public function dumpException(\Exception $value) {
        $this->Reporter->dumpException($value);
        return $this;
    }

    public function exceptionToString(\Exception $e) {
        return sprintf(
            "%s in %s line %d\n%s\n%s",
            get_class($Exception),
            $Exception->getFile(),
            $Exception->getLine(),
            $Exception->getMessage(),
            str_replace("\n", "\n\t", $Exception->getTraceAsString())
        );
    }

    public function dumpBacktrace() {
        $trace = debug_backtrace();
        array_shift($trace);
        $this->dump($trace, 'Backtrace');
        return $this;
    }

    public function createTimer($group, $message) {
        $Timer = new Carcass_DevTools_Timer($message);
        return $this->timers[$group][] = $Timer;
    }

    public function dumpTimers($clean_stopped = false) {
        $result = [];
        $group_results = [];
        foreach ($this->timers as $group => &$timers) {
            $group_total = 0;
            $group_results = [];
            $i = 0;
            foreach ($timers as $k => $Timer) {
                $value = $Timer->getValue();
                $group_results[ sprintf('%03d) %0.8f', ++$i, $value ?: 0) ] = preg_replace('/\s+/', ' ', $Timer->getTitle());
                if (null !== $value) {
                    $group_total += $value;
                }
                if ($clean_stopped && !$Timer->isRunning()) {
                    unset($timers[$k]);
                }
            }
            if ($clean_stopped && empty($timers)) {
                unset($this->timers[$group]);
            }
            $group_total = sprintf('%0.4f', $group_total);
            $this->dump($group_results, $group_total . ' ' .  trim(substr($group, 0, 15), '\\_ ') . '['.count($group_results).']', 'info');
        }
        return $this;
    }

    public function truncate($string) {
        if (!isset($this->truncate)) {
            $this->truncate = (int)Application\Instance::getConfigReader()->getPath('application.debug.truncate', self::TRUNCATE_DEFAULT) ?: false;
        }

        if ($this->truncate > 0 && mb_strlen($string) > $this->truncate) {
            $string = mb_substr($string, 0, $this->truncate - 10) . '...' . mb_substr($string, -7);
        }

        return $string;
    }

}
