<?php

namespace Carcass\Fs;

class LockFile {

    protected
        $scheme = '',
        $filename,
        $h = null,
        $unlink_on_destroy = true;

    public function __construct($filename) {
        $this->filename = $filename;
    }

    public static function constructTemporary($tmp_path = null, $prefix = 'lock_') {
        $tmp_filename = tempnam($tmp_path ?: sys_get_temp_dir(), $prefix);
        return new static($tmp_filename);
    }

    public function setScheme($scheme) {
        $this->scheme = $scheme ? strval($scheme) . '://' : '';
        return $this;
    }

    public function keep($bool_keep_file_on_destroy = true) {
        $this->unlink_on_destroy = !$bool_keep_file_on_destroy;
        return $this;
    }

    public function lock($lock_mode = LOCK_EX) {
        try {
            $hnd = fopen($this->scheme . $this->filename, empty($this->scheme) ? 'a+' : 'a');
            flock($hnd, $lock_mode);
            $this->h = $hnd;
        } catch (\Exception $e) {
            throw new \RuntimeException('Cannot lock: ' . $e->getMessage());
        }
        return $this;
    }

    public function truncate() {
        ftruncate($this->h(), 0);
        fseek($this->h(), 0);
        return $this;
    }

    public function release() {
        flock($this->h(), LOCK_UN);
        fclose($this->h());
        $this->h = null;
        return $this;
    }

    public function unlink() {
        file_exists($this->filename) && unlink($this->filename);
        return $this;
    }

    public function h() {
        if (null === $this->h) {
            throw new \LogicException('File not opened');
        }
        return $this->h;
    }

    public function __toString() {
        return $this->filename;
    }

    public function __destruct() {
        if ($this->unlink_on_destroy) {
            $this->unlink();
        }
        if ($this->h !== null) {
            $this->release();
        }
    }

}
