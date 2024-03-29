<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;

/**
 * Filesystem session storage implementation.
 *
 * Similar to default PHP session storage, stores serialized session data
 * in plain files, and triggers garbage collector with given probabilty.
 *
 * @package Carcass\Application
 */
class Web_Session_FilesystemStorage implements Web_Session_StorageInterface {

    const DEFAULT_SESSION_FILE_TEMPLATE = '%s.sess';
    const DEFAULT_SESSION_BIND_FILE_TEMPLATE = 'bind.%s.sess';
    const DEFAULT_GC_PROBABILITY        = 0.005;
    const DEFAULT_GC_EXPIRATION         = 1800;

    /** @var bool */
    protected $gc_executed = false;
    /** @var null|string */
    protected $directory = null;
    /** @var string */
    protected $file_tmpl = self::DEFAULT_SESSION_FILE_TEMPLATE;
    /** @var string */
    protected $bind_file_tmpl = self::DEFAULT_SESSION_BIND_FILE_TEMPLATE;
    /** @var float */
    protected $gc_probability = self::DEFAULT_GC_PROBABILITY;
    /** @var int */
    protected $gc_expiration = self::DEFAULT_GC_EXPIRATION;

    /**
     * @param string|null $directory Session files storage directory, defaults to system temporary directory
     * @param string|null $file_tmpl If given, overrides the default session file template. A sprintf pattern with '%s' for sid.
     */
    public function __construct($directory = null, $file_tmpl = null) {
        $this->directory = rtrim($directory ? : sys_get_temp_dir(), '/') . '/';
        $file_tmpl and $this->file_tmpl = $file_tmpl;
    }

    /**
     * @param float $gc_probability
     * @return $this
     */
    public function setGcProbability($gc_probability) {
        $this->gc_probability = $gc_probability;
        return $this;
    }

    /**
     * @param int $gc_expiration
     * @return $this
     */
    public function setGcExpiration($gc_expiration) {
        $this->gc_expiration = $gc_expiration;
        return $this;
    }

    /**
     * @param string $session_id
     * @return mixed
     */
    public function get($session_id) {
        if (file_exists($file = $this->getSessionFilePath($session_id))) {
            try {
                return (array)unserialize(file_get_contents($file));
            } catch (\Exception $e) {
                DI::getLogger()->logException($e);
            }
        }
        return [];
    }

    /**
     * @param string $session_id
     * @param array $data
     * @param $is_changed
     * @return $this
     */
    public function write($session_id, array $data, $is_changed) {
        $is_changed and file_put_contents($this->getSessionFilePath($session_id), serialize($data), LOCK_EX);
        return $this;
    }

    /**
     * @param string $session_id
     * @return $this
     */
    public function delete($session_id) {
        if (file_exists($file = $this->getSessionFilePath($session_id))) {
            unlink($file);
        }
        return $this;
    }

    /**
     * Returns session id bound to current bind_uid
     *
     * @param string $bind_uid
     * @return string|null
     */
    public function getBoundSid($bind_uid) {
        $filename = $this->getBoundSid($bind_uid);
        if (!file_exists($filename)) {
            return null;
        }
        try {
            return file_get_contents($filename);
        } catch (WarningException $e) {
            if (false !== strpos($e->getMessage(), 'No such file or directory')) {
                DI::getDebugger()->dumpException($e);
                DI::getLogger()->logException($e);
            }
            return null;
        }
    }

    /**
     * Updates the session id bound to current bind_uid
     *
     * @param string $bind_uid
     * @param string|null $session_id
     * @return $this
     */
    public function setBoundSid($bind_uid, $session_id) {
        $filename = $this->getBoundSid($bind_uid);
        if ($session_id) {
            file_put_contents($filename, $session_id);
        } elseif (file_exists($filename)) {
            try {
                unlink($filename);
            } catch (WarningException $e) {
                if (false !== strpos($e->getMessage(), 'No such file or directory')) {
                    DI::getDebugger()->dumpException($e);
                    DI::getLogger()->logException($e);
                }
            }
        }
        return $this;
    }

    /**
     * Triggers the garbage collector with a probability.
     */
    public function __destruct() {
        if ($this->gc_executed) {
            return;
        }
        if ($this->mustRunGc()) {
            $this->gc_executed = true;
            $this->gc();
        }
    }

    /**
     * @param string $session_id
     * @return string
     */
    protected function getSessionFilePath($session_id) {
        return $this->directory . sprintf($this->file_tmpl, $session_id);
    }

    /**
     * @param string $bind_uid
     * @return string
     */
    protected function getSessionBindFilePath($bind_uid) {
        return $this->directory . sprintf($this->bind_file_tmpl, $bind_uid);
    }

    protected function gc() {
        $expiration_ts = Corelib\TimeTools::getTime() - $this->gc_expiration;
        foreach (glob($this->getSessionFilePath('*'), GLOB_NOSORT) as $file) {
            try {
                if (filemtime($file) <= $expiration_ts) {
                    unlink($file);
                }
            } catch (\Exception $e) {
                DI::getLogger()->logException($e, 'Warning');
            }
        }
    }

    /**
     * @return bool
     */
    protected function mustRunGc() {
        return mt_rand(0, PHP_INT_MAX) <= PHP_INT_MAX * $this->gc_probability;
    }

}
