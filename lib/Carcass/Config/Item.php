<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Config;

use Carcass\Corelib;

/**
 * Configuration item
 *
 * @method Item|mixed get($key, $default_value = null)
 * @method Item|mixed __get($key)
 *
 * @package Carcass\Config
 */
class Item implements ItemInterface, \Iterator, \ArrayAccess, \Countable, Corelib\ExportableInterface {
    use Corelib\DatasourceTrait, Corelib\ExportableTrait, Corelib\ArrayObjectTrait, Corelib\ArrayObjectDatasourceTrait {
        /** @noinspection PhpUndefinedClassInspection */
        Corelib\ArrayObjectDatasourceTrait::hasArrayObjectItemByKey insteadof Corelib\ArrayObjectTrait;
        /** @noinspection PhpUndefinedClassInspection */
        Corelib\ArrayObjectDatasourceTrait::getArrayObjectItemByKey insteadof Corelib\ArrayObjectTrait;
    }

    protected $storage = [];

    public function __construct(array $data = null) {
        $data and $this->import($data);
    }

    /**
     * @param string $path dot-separated
     * @param mixed $default_value
     * @return ItemInterface|null|string
     */
    public function getPath($path, $default_value = null) {
        if (false === $key = strtok($path, '.')) {
            return $default_value;
        }
        $subpath = strtok(null);
        try {
            $Item = $this->getItem($key);
            if ($subpath !== false) {
                if ($Item instanceof ItemInterface) {
                    return $Item->getPath($subpath, $default_value);
                }
                return $default_value;
            }
            return $Item;
        } catch (\OutOfBoundsException $e) {
            return $default_value;
        }
    }

    /**
     * @param string $path dot-separated
     * @param mixed $default_value
     * @return array
     */
    public function exportArrayFrom($path, $default_value = []) {
        $Item = $this->getPath($path);
        return $Item instanceof Corelib\ExportableInterface ? $Item->exportArray() : $default_value;
    }

    /**
     * @param string $path
     * @param array $default_value
     * @return \Carcass\Corelib\Hash
     */
    public function exportHashFrom($path, $default_value = []) {
        return new Corelib\Hash($this->exportArrayFrom($path, $default_value));
    }

    /**
     * @param $key
     * @return ItemInterface
     * @throws \OutOfBoundsException
     */
    protected function getItem($key) {
        if (!$this->has($key)) {
            throw new \OutOfBoundsException("No configuration item for '$key' found");
        }
        return $this->get($key);
    }

    /**
     * @param $key
     * @return ItemInterface|null|string
     */
    /** @noinspection PhpHierarchyChecksInspection */
    protected function &getRef($key) {
        $value = $this->getPath($key);
        return $value;
    }

    /**
     * @return array
     */
    /** @noinspection PhpHierarchyChecksInspection */
    protected function &getDataArrayPtr() {
        return $this->storage;
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function import(array $data) {
        foreach ($data as $key => $value) {
            $this->storage[$key] = is_array($value) ? (new self)->import($value) : $value;
        }
        return $this;
    }

}
