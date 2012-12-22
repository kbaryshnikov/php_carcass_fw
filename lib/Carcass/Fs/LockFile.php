<?php

namespace Carcass\Fs;

class LockFile extends \SplFileObject {

    protected
        $unlink_on_destroy = true;

    public function __construct($filename, $mode = 'a+') {
        parent::__construct($filename, $mode);
    }

    public static function constructTemporary($tmp_path = null, $prefix = 'lock_', $mode = 'a+') {
        $tmp_filename = tempnam($tmp_path ?: sys_get_temp_dir(), $prefix);
        return new static($tmp_filename, $mode);
    }

    public function keep($bool_keep_file_on_destroy = true) {
        $this->unlink_on_destroy = !$bool_keep_file_on_destroy;
        return $this;
    }

    public function lock($lock_mode = LOCK_EX) {
        return $this->flock($lock_mode);
    }

    public function truncate() {
        return $this->ftruncate(0) && $this->fseek(0);
    }

    public function release() {
        return $this->flock(LOCK_UN);
    }

    public function unlink() {
        file_exists($this->getPathname()) && unlink($this->getPathname());
        return $this;
    }

    public function __destruct() {
        if ($this->unlink_on_destroy) {
            $this->unlink();
        }
        $this->release();
    }

}
