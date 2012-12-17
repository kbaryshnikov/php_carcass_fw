<?php

namespace Carcass\Process;

use Carcass\Application as Application;

class Pidfile {

    const
        PID_FILE_PREFIX = 'ccss_',
        PID_FILE_EXT = '.pid';
        ERR_EPERM = 1, // EPERM/ESRCH values on most *nix systems
        ERR_ESRCH = 3; // No such process

    protected
        $folder = null,
        $prefix = null,
        $ext = null,
        $basename = null,
        $filename,
        $written = false;

    /**
     * @param string $name process name
     */
    public function __construct($name) {
        $this->basename = $name;
        $this->setFilename($this->basename);
    }

    /**
     * sets the pidfile folder
     * 
     * @param string|null $folder, null for system temp dir
     * @return self
     */
    public function setFolder($folder) {
        $this->folder = $folder;
        $this->setFilename($this->basename);
        return $this;
    }

    /**
     * sets the pidfile extension
     * 
     * @param string|null $ext, null for default '.pid'
     * @return self
     */
    public function setExt($ext) {
        $this->ext = $ext ? ('.' . ltrim($ext, '.')) : null;
        $this->setFilename($this->basename);
        return $this;
    }

    /**
     * sets the pidfile prefix
     * 
     * @param string|null $prefix, null for default prefix 'ccss_'
     * @return self
     */
    public function setPrefix($prefix) {
        $this->prefix = $prefix;
        $this->setFilename($this->basename);
        return $this;
    }

    public function getPidFile($name) {
        $folder = rtrim($this->folder ?: sys_get_temp_dir(), '/') . '/';
        return $folder . ($this->prefix ?: self::PID_FILE_PREFIX) . $name . ($this->ext ?: self::PID_FILE_EXT);
    }

    /**
     * Checks pidfile and process extistance
     * @return pid|null
     */
    public function check($compare_with_current = true) {
        if (!file_exists($this->pidfile)) {
            Application\Instance::getLogger()->logEvent('DebugVerbose', $this->basename . ': Pidfile ' . $this->pidfile . ' not exists');
            return null;
        }

        if (0 >= $pid = (int)@file_get_contents($this->pidfile)) {
            Application\Instance::getLogger()->logEvent('DebugVerbose', $this->basename . ': Pidfile ' . $this->pidfile . ' has no pid');
            return null;
        }

        if ($compare_with_current && $pid == $this->getCurrentPid()) {
            Application\Instance::getLogger()->logEvent('DebugVerbose', $this->basename . ': Saved pid matches current pid #' . $pid);
            return null;
        }

        if (!posix_kill($pid, 0)) {
            $errno = posix_get_last_error();

            switch ($errno) {

                case self::ERR_ESRCH:
                    Application\Instance::getLogger()->logEvent('DebugVerbose', $this->basename . ': kill(' . $pid . ',0) errno is ' . $errno . ' (ERR_ESRCH): no such process');
                    @$this->unlink();
                    return null;

                case self::ERR_EPERM:
                    Application\Instance::getLogger()->logEvent('DebugVerbose', $this->basename . ': kill(' . $pid . ',0) errno is ' . $errno . ' (ERR_EPERM): does not seem to be our process'); 
                    @$this->unlink();
                    return null;

                default:
                    Application\Instance::getLogger()->logEvent('DebugVerbose', $this->basename . ': kill(' . $pid . ',0) errno is ' . $errno);

            }
        }

        Application\Instance::getLogger()->logEvent('DebugVerbose', $this->basename . ': already running; pidfile ' . $this->pidfile . ' contains pid #' . $pid);

        return $pid;
    }

    public function writePidfile() {
        $this->pid = $this->getCurrentPid();
        file_put_contents($this->pidfile, $this->pid."\n");
        $this->written = true;
    }

    /**
     * @return void
     */
    public function delete() {
        if (empty($this->pidfile) || $this->pidChanged()) {
            return;
        }

        $this->unlink();

        $this->pidfile = null;
    }

    protected function unlink() {
        file_exists($this->pidfile) and unlink($this->pidfile);
    }

    protected function pidChanged() {
        return isset($this->pid) && $this->pid != $this->getCurrentPid();
    }

    protected function setFilename($name) {
        $this->pidfile = $this->getPidFile($name);
    }

    protected function getCurrentPid() {
        return (int)posix_getpid();
    }

}
