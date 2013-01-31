<?php

namespace Carcass\Corelib;

class Response implements ResponseInterface {
    
    protected
        $status = null,
        $is_buffering = false,
        $buffer = '';

    public function isBuffering() {
        return $this->is_buffering;
    }

    public function begin() {
        if ($this->is_buffering) {
            throw new \LogicException("Already buffering");
        }
        $this->is_buffering = true;
        $this->buffer = '';
        return $this;
    }

    public function commit(Callable $fnBeforeWrite = null) {
        if (!$this->is_buffering) {
            throw new \LogicException("Not buffering");
        }
        $this->is_buffering = false;
        $fnBeforeWrite and $fnBeforeWrite();
        $this->doWrite($this->buffer);
        $this->buffer = '';
        return $this;
    }

    public function rollback(Callable $fnOnRollback = null) {
        if (!$this->is_buffering) {
            throw new \LogicException("Not buffering");
        }
        $this->is_buffering = false;
        $fnOnRollback and $fnOnRollback();
        $this->buffer = '';
        return $this;
    }

    public function write($string) {
        if ($this->is_buffering) {
            $this->buffer .= $string;
        } else {
            $this->doWrite($string);
        }
        return $this;
    }

    public function writeLn($string) {
        $this->write($string . "\n");
        return $this;
    }

    public function writeError($string) {
        fprintf(STDERR, $string);
        return $this;
    }

    public function writeErrorLn($string) {
        $this->writeError($string . "\n");
        return $this;
    }

    public function setStatus($status = null) {
        $this->status = $status === null ? null : intval($status);
        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    protected function doWrite($string) {
        print $string;
    }

}
