<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Fs;

/**
 * LockFile implementation, based on SplFileObject
 * @package Carcass\Fs
 */
class LockFile extends \SplFileObject {

    /**
     * @var bool
     */
    protected $unlink_on_destroy = true;

    /**
     * @param $filename
     * @param string $mode
     */
    public function __construct($filename, $mode = 'a+') {
        parent::__construct($filename, $mode);
    }

    /**
     * @param string|null $tmp_path if null, use the system temporary directory
     * @param string $prefix
     * @param string $mode
     * @return LockFile
     */
    public static function constructTemporary($tmp_path = null, $prefix = 'lock_', $mode = 'a+') {
        $tmp_filename = tempnam($tmp_path ?: sys_get_temp_dir(), $prefix);
        return new static($tmp_filename, $mode);
    }

    /**
     * @param bool $bool_keep_file_on_destroy
     * @return $this
     */
    public function keep($bool_keep_file_on_destroy = true) {
        $this->unlink_on_destroy = !$bool_keep_file_on_destroy;
        return $this;
    }

    /**
     * @param int $lock_mode
     * @return bool
     */
    public function lock($lock_mode = LOCK_EX) {
        return $this->flock($lock_mode);
    }

    /**
     * @return bool
     */
    public function truncate() {
        return $this->ftruncate(0) && $this->fseek(0);
    }

    /**
     * @return bool
     */
    public function release() {
        return $this->flock(LOCK_UN);
    }

    /**
     * @return $this
     */
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
