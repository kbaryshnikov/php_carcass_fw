<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Base ResponseInterface implementation
 *
 * @package Carcass\Corelib
 */
class Response implements ResponseInterface {

    protected $status = null;
    protected $is_buffering = false;
    protected $buffer = '';

    /**
     * @return bool
     */
    public function isBuffering() {
        return $this->is_buffering;
    }

    /**
     * @return $this
     * @throws \LogicException
     */
    public function begin() {
        if ($this->is_buffering) {
            throw new \LogicException("Already buffering");
        }
        $this->is_buffering = true;
        $this->buffer = '';
        return $this;
    }

    /**
     * @param callable $fnBeforeWrite
     * @return $this
     * @throws \LogicException
     */
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

    /**
     * @param callable $fnOnRollback
     * @return $this
     * @throws \LogicException
     */
    public function rollback(Callable $fnOnRollback = null) {
        if (!$this->is_buffering) {
            throw new \LogicException("Not buffering");
        }
        $this->is_buffering = false;
        $fnOnRollback and $fnOnRollback();
        $this->buffer = '';
        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function write($string) {
        if ($this->is_buffering) {
            $this->buffer .= $string;
        } else {
            $this->doWrite($string);
        }
        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function writeLn($string) {
        $this->write($string . "\n");
        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function writeError($string) {
        fprintf(STDERR, $string);
        return $this;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function writeErrorLn($string) {
        $this->writeError($string . "\n");
        return $this;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status) {
        $this->status = empty($status) ? null : intval($status);
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }

    protected function doWrite($string) {
        print $string;
    }

}
