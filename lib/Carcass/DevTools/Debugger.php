<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\DevTools;

use Carcass\Application as Application;

/**
 * Debugger
 * @package Carcass\DevTools
 */
class Debugger {

    const TRUNCATE_DEFAULT = 300;

    protected $truncate = 0;
    protected $timers = [];

    /**
     * @var BaseReporter
     */
    protected $Reporter;

    /**
     * @param BaseReporter $Reporter
     */
    public function __construct(BaseReporter $Reporter = null) {
        if (null !== $Reporter) {
            $this->setReporter($Reporter);
        }
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return true;
    }

    /**
     * @param BaseReporter $Reporter
     * @return $this
     */
    public function setReporter(BaseReporter $Reporter) {
        $this->Reporter = $Reporter;
        return $this;
    }

    /**
     * @param mixed $value
     * @param string|null $header
     * @param $severity
     * @return $this
     */
    public function dump($value, $header = null, $severity = null) {
        $this->Reporter->dump($header === null ? $value : [$header => $value], $severity);
        return $this;
    }

    /**
     * @param \Exception $value
     * @return $this
     */
    public function dumpException(\Exception $value) {
        $this->Reporter->dumpException($value);
        return $this;
    }

    /**
     * @param \Exception $Exception
     * @return string
     */
    public function exceptionToString(\Exception $Exception) {
        return sprintf(
            "%s in %s line %d\n%s\n%s",
            get_class($Exception),
            $Exception->getFile(),
            $Exception->getLine(),
            $Exception->getMessage(),
            str_replace("\n", "\n\t", $Exception->getTraceAsString())
        );
    }

    /**
     * @return $this
     */
    public function dumpBacktrace() {
        $trace = debug_backtrace();
        array_shift($trace);
        $this->dump($trace, 'Backtrace');
        return $this;
    }

    /**
     * @param string $group
     * @param string $message
     * @return Timer
     */
    public function createTimer($group, $message) {
        $Timer = new Timer($message);
        return $this->timers[$group][] = $Timer;
    }

    /**
     * @param string $group
     * @param Timer $Timer
     * @return $this
     */
    public function registerTimer($group, Timer $Timer) {
        $this->timers[$group][] = $Timer;
        return $this;
    }

    /**
     * @param bool $clean_stopped
     * @return $this
     */
    public function dumpTimers($clean_stopped = false) {
        $group_results = [];
        foreach ($this->timers as $group => &$timers) {
            $group_total = 0;
            $group_results = [];
            $i = 0;
            foreach ($timers as $k => $Timer) {
                /** @var Timer $Timer */
                $value = $Timer->getValue();
                $group_results[ sprintf('%3d: %0.8f', ++$i, $value ?: 0) ] = preg_replace('/\s+/', ' ', $Timer->getTitle());
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

    /**
     * @param string $string
     * @return string
     */
    public function truncate($string) {
        if (!isset($this->truncate)) {
            $this->truncate = (int)Application\DI::getConfigReader()->getPath('application.debug.truncate', self::TRUNCATE_DEFAULT) ?: 0;
        }

        if ($this->truncate > 0 && mb_strlen($string) > $this->truncate) {
            $string = mb_substr($string, 0, $this->truncate - 10) . '...' . mb_substr($string, -7);
        }

        return $string;
    }

    public function finalize() {
        $this->dumpTimers(true);
    }

}
