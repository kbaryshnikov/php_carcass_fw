<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Less;

use Carcass\Fs;

/**
 * LESS Dispatcher
 * @package Carcass\Less
 */
class Dispatcher {

    /**
     * @var Compiler|null
     */
    protected $Compiler = null;
    /**
     * @var Cacher_Interface|null
     */
    protected $Cacher = null;

    protected $less_path;
    protected $target_path = null;

    /**
     * @param Cacher_Interface $Cacher
     * @param string $less_path
     * @param string|null $target_path
     */
    public function __construct(Cacher_Interface $Cacher, $less_path, $target_path = null) {
        $this->Cacher = $Cacher;
        $this->setLessPath($less_path);
        $target_path and $this->setTargetPath($target_path);
    }

    /**
     * @param string $less_path
     * @return $this
     */
    public function setLessPath($less_path) {
        $this->less_path = rtrim($less_path, '/');
        return $this;
    }

    /**
     * @param string $target_path
     * @return $this
     */
    public function setTargetPath($target_path) {
        $this->target_path = rtrim($target_path, '/');
        return $this;
    }

    /**
     * @param string $file
     * @param int|null $mtime
     * @param string|null $target_file_name
     * @throws \LogicException
     * @return string
     */
    public function compile($file, &$mtime = null, $target_file_name = null) {
        if (!$this->target_path) {
            throw new \LogicException("target path is undefined");
        }
        $target_file_name = '/' . $target_file_name ?: (md5($file) . '.css');
        $result = $this->compileFileToLessString($file, $mtime);
        if (!$result) {
            throw new \LogicException("Empty less compilation result");
        }
        Fs\Directory::mkdirIfNotExists($this->target_path);
        file_put_contents($this->target_path . $target_file_name, $result, LOCK_EX);
        return $target_file_name;
    }

    /**
     * @param string $file
     * @param int|null $mtime
     * @return mixed
     */
    public function compileFileToLessString($file, &$mtime = null) {
        $file_name = $this->less_path . '/' . ltrim($file, '/');

        $less_cache = $this->Cacher->get($file_name);
        if (!is_array($less_cache) || empty($less_cache)) {
            $less_cache = null;
        }

        $result = $this->getCompiler()->cachedCompile( $less_cache ?: $file_name );

        if (!$less_cache || $result['updated'] > $less_cache['updated']) {
            $this->Cacher->put($file_name, $result);
        }

        $mtime = $result['updated'];

        return $result['compiled'];
    }

    /**
     * @return Compiler
     */
    protected function getCompiler() {
        if (null === $this->Compiler) {
            $this->Compiler = $this->assembleCompiler();
        }
        return $this->Compiler;
    }

    /**
     * @return Compiler
     */
    protected function assembleCompiler() {
        $Compiler = new Compiler;
        $Compiler->setImportDir($this->less_path);
        $Compiler->setFormatter('Compressed');
        return $Compiler;
    }

}
