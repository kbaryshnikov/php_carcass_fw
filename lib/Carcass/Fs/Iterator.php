<?php

namespace Carcass\Fs;

use Carcass\Corelib;

class Iterator implements \Iterator, Corelib\ExportableInterface {

    protected
        $folder,
        $files = null,
        $filter_mask = null,
        $sort = true,
        $return_full_path = true,
        $include_files = true,
        $include_folders = false,
        $include_hidden = false;

    /**
     * @param string $folder Folder to scan
     * @param array $options [ 'mask' => string mask, 'return_full_path' => bool, 'include_folders' => bool, 'include_files' => bool,
     *                         'include_hidden' => bool, 'sort' => bool ]
     */
    public function __construct($folder, array $options = []) {
        $this->setFolder($folder);
        $this->setOptions($options);
    }

    /**
     * @param array $options see the constructor $options argument
     * @return self
     */
    public function setOptions(array $options) {
        foreach ($options as $name => $value) {
            $method = 'set' . str_replace('_', '', $name);
            if (!method_exists($this, $method)) {
                throw new \InvalidArgumentException("Invalid option: '$name'");
            }
            $this->$method($value);
        }
        return $this;
    }

    /**
     * @param string $folder Folder to scan
     * @return self
     */
    public function setFolder($folder) {
        $folder = rtrim(realpath($folder), '/');
        if (!file_exists($folder) || !is_dir($folder)) {
            throw new \RuntimeException('Folder not exists or unreadable: "' . $folder . '"');
        }
        $this->setProp('folder', $folder);
        return $this;
    }

    /**
     * setFilterMask 
     * 
     * @param string|array|NULL $fnmatch_mask, see fnmatch() for syntax
     * @return self
     */
    public function setFilterMask($fnmatch_mask) {
        if (!is_string($fnmatch_mask) && !is_array($fnmatch_mask) && !is_null($fnmatch_mask)) {
            throw new \InvalidArgumentException('String, array, or null required');
        }
        if (is_string($fnmatch_mask)) {
            $fnmatch_mask = [$fnmatch_mask]; 
        }
        $this->setProp('filter_mask', $fnmatch_mask ?: null);
        if ($fnmatch_mask && array_filter($fnmatch_mask, function($value) { return substr($value, 0, 1) == '.'; })) {
            $this->setProp('include_hidden', true);
        }
        return $this;
    }

    /**
     * setReturnFullPath
     * 
     * @param bool $bool_return_full_path if false, only filenames are returned
     * @return self
     */
    public function setReturnFullPath($bool_return_full_path) {
        $this->setProp('return_full_path', (bool)$bool_return_full_path);
        return $this;
    }

    /**
     * setIncludeFolders 
     * 
     * @param bool $bool_include_folders 
     * @return self
     */
    public function setIncludeFolders($bool_include_folders) {
        $this->setProp('include_folders', (bool)$bool_include_folders);
        return $this;
    }

    /**
     * setIncludeFiles
     * 
     * @param bool $bool_include_files
     * @return self
     */
    public function setIncludeFiles($bool_include_files) {
        $this->setProp('include_files', (bool)$bool_include_files);
        return $this;
    }

    /**
     * setIncludeHidden 
     * 
     * @param bool $bool_include_hidden 
     * @return self
     */
    public function setIncludeHidden($bool_include_hidden) {
        $this->setProp('include_hidden', (bool)$bool_include_hidden);
        return $this;
    }

    /**
     * setSort 
     * 
     * @param bool $bool_sort 
     * @return self
     */
    public function setSort($bool_sort) {
        $this->setProp('sort', (bool)$bool_sort);
        return $this;
    }

    /**
     * Iterator implementation
     * @return mixed
     */
    public function rewind() {
        $this->getFiles();
        reset($this->files);
    }

    /**
     * Iterator implementation
     * @return mixed
     */
    public function current() {
        $this->getFiles();
        return current($this->files);
    }

    /**
     * Iterator implementation
     * @return mixed
     */
    public function key() {
        $this->getFiles();
        return key($this->files);
    }

    /**
     * Iterator implementation
     * @return mixed
     */
    public function next() {
        $this->getFiles();
        return next($this->files);
    }

    /**
     * Iterator implementation
     * @return bool
     */
    public function valid() {
        $this->getFiles();
        return ( $this->current() !== false );
    }

    /**
     * @return array
     */
    public function exportArray() {
        return $this->getFiles();
    }

    protected function getFiles() {
        if (null === $this->files) {
            $this->loadFilesList();
        }
        return $this->files;
    }

    protected function setProp($prop, $value) {
        $old_value = $this->$prop;
        $this->$prop = $value;
        if ($old_value !== $value) {
            $this->files = null;
        }
    }

    protected function loadFilesList() {
        $result = [];

        $dir_handle = opendir($this->folder);
        if ($dir_handle) {
            while (false !== ($filename = readdir($dir_handle))) {
                $is_dir = is_dir($this->folder . '/' . $filename);
                if ($filename == '.' || $filename == '..' 
                    || (!$this->include_folders && $is_dir)
                    || (!$this->include_files && !$is_dir)
                    || (!$this->include_hidden && $filename{0} == '.')
                ) {
                    continue;
                }
                if (null === $this->filter_mask || $this->fnmatch($filename)) {
                    $result[] = ($this->return_full_path ? ($this->folder . '/') : '') . $filename . (is_dir($filename) ? '/' : '');
                }
            }
            closedir($dir_handle);
        }

        if ($this->sort) {
            sort($result);
        }

        $this->files = $result;
        return $result;
    }

    protected function fnmatch($filename) {
        if (empty($this->filter_mask)) {
            return false;
        }

        foreach ($this->filter_mask as $fn_match) {
            if (fnmatch($fn_match, $filename)) {
                return true;
            }
        }
        return false;
    }

}
