<?php

namespace Carcass\Process;

use Carcass\Application\DI;
use Carcass\Corelib;

/**
 * Class CrontabBuilder
 * @package Carcass\Process
 */
class CrontabBuilder implements Corelib\ExportableInterface {

    protected $php_path = null;
    protected $cron_line_format = '{{schedule}} {{command}} 2>&1 >/dev/null &';
    protected $cron_prefix_lines = ['MAILTO=""'];

    public function __construct() {
        $this->detectPhpCliPath();
    }

    /**
     * @param string $tpl
     * @return $this
     */
    public function setCronLineFormat($tpl) {
        $this->cron_line_format = $tpl;
        return $this;
    }

    /**
     * @param string $cron_prefix
     * @return $this
     */
    public function addCronPrefix($cron_prefix) {
        $this->cron_prefix_lines[] = $cron_prefix;
        return $this;
    }

    /**
     * @param Corelib\ResponseInterface $Response
     */
    public function writeTo(Corelib\ResponseInterface $Response) {
        $Response->write($this->export());
    }

    /**
     * @return string
     */
    public function export() {
        return join("", $this->exportArray());
    }

    /**
     * @param array $cron_prefix_lines
     * @return $this
     */
    public function replaceCronPrefixLines(array $cron_prefix_lines) {
        $this->cron_prefix_lines = $cron_prefix_lines;
        return $this;
    }

    protected function getPathToScriptRunner() {
        return DI::getPathManager()->getAbsolutePath('run');
    }

    protected function getScriptExecutionLine($schedule, $script_command) {
        return strtr(
            $this->cron_line_format, [
                '{{schedule}}' => $schedule,
                '{{command}}'  => $this->getPathToScriptRunner() . ' ' . $script_command
            ]
        );
    }

    protected function getCrontabPrefix() {
        return $this->cron_prefix_lines;
    }

    protected function getCronTab() {
        return DI::getConfigReader()->exportArrayFrom('crontab');
    }

    protected function detectPhpCliPath() {
        $php_path = trim(shell_exec('which php'));
        if (empty($php_path)) {
            throw new \RuntimeException("Could not detect the PHP cli interpreter path!\n");
        }
        $this->php_path = $php_path;
    }

    /**
     * @return array
     */
    public function exportArray() {
        $result = [];
        $prefixes = $this->getCrontabPrefix();
        if (!empty($prefixes)) {
            foreach ($prefixes as $line) {
                $result[] = $line . "\n";
            }
        }
        $crontab = $this->getCronTab();
        if (!empty($crontab)) {
            foreach ($crontab as $script_class => $schedule_string) {
                $result[] = $this->getScriptExecutionLine($schedule_string, $script_class) . "\n";
            }
        }
        return $result;
    }
}
