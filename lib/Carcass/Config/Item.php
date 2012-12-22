<?php

namespace Carcass\Config;

use Carcass\Corelib as Corelib;

class Item implements ItemInterface, \Iterator, \ArrayAccess, \Countable, Corelib\ExportableInterface {
    use Corelib\DatasourceTrait, Corelib\ExportableTrait, Corelib\ArrayObjectTrait, Corelib\ArrayObjectDatasourceTrait {
        Corelib\ArrayObjectDatasourceTrait::hasArrayObjectItemByKey insteadof Corelib\ArrayObjectTrait;
        Corelib\ArrayObjectDatasourceTrait::getArrayObjectItemByKey insteadof Corelib\ArrayObjectTrait;
    }

    protected $storage = [];

    public function __construct(array $data = null) {
        $data and $this->import($data);
    }

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

    public function exportArrayFrom($path, $default_value = []) {
        $Item = $this->getPath($path);
        return $Item instanceof Corelib\ExportableInterface ? $Item->exportArray() : $default_value;
    }

    public function exportHashFrom($path, $default_value = []) {
        return new Corelib\Hash($this->exportArrayFrom($path, $default_value));
    }

    protected function getItem($key) {
        if (!$this->has($key)) {
            throw new \OutOfBoundsException("No configuration item for '$key' found");
        }
        return $this->get($key);
    }

    protected function &getRef($key) {
        $value = $this->getPath($key);
        return $value;
    }

    protected function &getDataArrayPtr() {
        return $this->storage;
    }

    protected function import(array $data) {
        foreach ($data as $key => $value) {
            $this->storage[$key] = is_array($value) ? (new self)->import($value) : $value;
        }
        return $this;
    }

}
