<?php

namespace Carcass\Application;

class Web_Session_FilesystemStorage implements Web_Session_StorageInterface {

    const
        DEFAULT_SESSION_FILE_TEMPLATE = '%s.sess',
        DEFAULT_GC_PROBABILITY = 0.005,
        DEFAULT_GC_EXPIRATION  = 1800;

    protected
        $gc_executed = false,
        $directory = null,
        $file_tmpl = self::DEFAULT_SESSION_FILE_TEMPLATE,
        $gc_probability = self::DEFAULT_GC_PROBABILITY,
        $gc_expiration  = self::DEFAULT_GC_EXPIRATION;

    public function __construct($directory = null, $file_tmpl = null) {
        $this->directory = rtrim($directory ?: sys_get_temp_dir(), '/') . '/';
        $file_tmpl and $this->file_tmpl = $file_tmpl;
    }

    public function setGcProbability($gc_probability) {
        $this->gc_probability = $gc_probability;
        return $this;
    }

    public function setGcExpiration($gc_expiration) {
        $this->gc_expiration = $gc_expiration;
        return $this;
    }

    public function get($session_id) {
        if (file_exists($file = $this->getSessionFilePath($session_id))) {
            try {
                return (array)unserialize(file_get_contents($file));
            } catch (\Exception $e) {
                Injector::getLogger()->logException($e);
            }
        } else {
            return [];
        }
    }
    
    public function write($session_id, array $data) {
        file_put_contents($this->getSessionFilePath($session_id), serialize($data), LOCK_EX);
    }

    public function delete($session_id) {
        if (file_exists($file = $this->getSessionFilePath($session_id))) {
            unlink($file);
        } 
    }

    public function __destruct() {
        if ($this->gc_executed) {
            return;
        }
        if ($this->mustRunGc()) {
            $this->gc_executed = true;
            $this->gc();
        }
    }

    protected function getSessionFilePath($session_id) {
        return $this->directory . sprintf($this->file_tmpl, $session_id);
    }

    protected function gc() {
        $expiration_ts = time() - $this->gc_expiration;
        foreach (glob($this->prefix . '*' . $this->ext, GLOB_NOSORT) as $file) {
            try {
                if (filemtime($file) <= $expiration_ts) {
                    unlink($file);
                }
            } catch (\Exception $e) {
                Injector::getLogger()->logException($e);
            }
        }
    }

    protected function mustRunGc() {
        return mt_rand(0, PHP_INT_MAX) <= PHP_INT_MAX * $this->gc_probability;
    }
    
}
