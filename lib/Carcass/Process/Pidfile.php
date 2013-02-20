<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Process;

use Carcass\Application\Injector;
use Carcass\Fs;

/**
 * Pidfile manager
 * @package Carcass\Process
 */
class Pidfile {

    const
        PID_FILE_EXT = '.pid',
        ERR_EPERM = 1, // EPERM/ESRCH values on most *nix systems
        ERR_ESRCH = 3; // No such process

    protected
        $pid = null,
        $folder = null,
        $basename = null,
        $pidfile = null,
        $written = false;

    /**
     * @param string $name process name
     * @param string $folder pidfile folder, default is system tempdir
     */
    public function __construct($name, $folder = null) {
        $this->basename = $name;
        $this->folder = $folder ?: null;
        $this->setFilename($this->basename);
    }

    /**
     * @param string $name 
     * @return string pidfile realpath
     */
    public function getPidFileRealpath($name) {
        $folder = rtrim($this->folder ?: sys_get_temp_dir(), '/') . '/';
        return $folder . $name . self::PID_FILE_EXT;
    }

    /**
     * Checks pidfile and process extistance
     * @param int $pid pid number, null for current pid, false to skip check
     * @return int|null
     */
    public function check($pid = null) {
        if (!file_exists($this->pidfile)) {
            Injector::getLogger()->logEvent('Debug', $this->basename . ': Pidfile ' . $this->pidfile . ' not exists');
            return null;
        }

        if (0 >= $file_pid = (int)@file_get_contents($this->pidfile)) {
            Injector::getLogger()->logEvent('Debug', $this->basename . ': Pidfile ' . $this->pidfile . ' has no pid');
            return null;
        }

        if ($pid === null) {
            $pid = $this->getCurrentPid();
        }

        if (false !== $pid && $pid == $file_pid) {
            Injector::getLogger()->logEvent('Debug', $this->basename . ': Saved pid #' . $file_pid . ' matches current pid #' . $pid);
            return null;
        }

        if (posix_kill($file_pid, 0)) {
            // already running
            return $file_pid;
        }

        switch (posix_get_last_error()) {
            case self::ERR_ESRCH:
            case self::ERR_EPERM:
                @$this->unlink();
                return null;
                break;
            default:
                Injector::getLogger()->logEvent('Notice', $this->basename . ': kill(' . $file_pid . ',0) errno is ' . $errno . '; assuming the process is running');
                return $file_pid;
        }
    }

    /**
     * @return $this
     */
    public function writePidfile() {
        $this->pid = $this->getCurrentPid();
        Fs\Directory::mkdirIfNotExists(dirname($this->pidfile));
        file_put_contents($this->pidfile, $this->pid."\n");
        $this->written = true;
        return $this;
    }

    /**
     * @return self
     */
    public function delete() {
        if (!empty($this->pidfile) && $this->pidChanged()) {
            $this->unlink();
            $this->pidfile = null;
        }
        return $this;
    }

    /**
     * return bool
     */
    protected function unlink() {
        return file_exists($this->pidfile) and unlink($this->pidfile);
    }

    /**
     * @return bool
     */
    protected function pidChanged() {
        return isset($this->pid) && $this->pid != $this->getCurrentPid();
    }

    /**
     * @param $name
     */
    protected function setFilename($name) {
        $this->pidfile = $this->getPidFileRealpath($name);
    }

    /**
     * @return int
     */
    protected function getCurrentPid() {
        return (int)posix_getpid();
    }

}
