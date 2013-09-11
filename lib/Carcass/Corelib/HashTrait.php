<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Hash trait: almost complete Hash/ArrayObject/ArrayAccess implementation based on core traits
 * @package Carcass\Corelib
 */
trait HashTrait {
    use DatasourceRefTrait, DataReceiverTrait, ExportableTrait, RenderableTrait, ArrayObjectTrait, ArrayObjectDatasourceTrait, ArrayObjectDataReceiverTrait {
        ArrayObjectDatasourceTrait::hasArrayObjectItemByKey insteadof ArrayObjectTrait;
        ArrayObjectDatasourceTrait::getArrayObjectItemByKey insteadof ArrayObjectTrait;
        ArrayObjectDataReceiverTrait::setArrayObjectItemByKey insteadof ArrayObjectTrait;
        ArrayObjectDataReceiverTrait::unsetArrayObjectItemByKey insteadof ArrayObjectTrait;
    }
    use FilterableDatasourceTrait;

    protected $storage = [];

    /**
     * @return $this
     */
    public function clear() {
        $this->storage = [];
        $this->untaint();
        return $this;
    }

    /**
     * @param array $clone_values
     * @return array
     */
    protected function deepClone(array &$clone_values = null) {
        $set_result = false;
        $result = [];
        if ($clone_values === null) {
            $clone_values = &$this->storage;
            $set_result = true;
        }
        foreach ($clone_values as $key => &$value) {
            if (is_array($value)) {
                $result[$key] = $this->deepClone($value);
            } elseif (is_object($value)) {
                $result[$key] = clone $value;
            } else {
                $result[$key] = $value;
            }
        }
        if ($set_result) {
            $this->storage = $result;
        }
        return $result;
    }

    /**
     * @param \Traversable|array $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function merge(/* Traversable */ $data) {
        if (!ArrayTools::isTraversable($data)) {
            throw new \InvalidArgumentException('Argument is not traversable');
        }
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @return array
     */
    /** @noinspection PhpHierarchyChecksInspection */
    protected function &getDataArrayPtr() {
        return $this->storage;
    }

    /**
     * @return array
     */
    /** @noinspection PhpHierarchyChecksInspection */
    public function getRenderArray() {
        return $this->storage;
    }

    /**
     * @return string
     */
    protected static function getClass() {
        return get_called_class();
    }

    /**
     * @param $instance
     * @return bool
     */
    /** @noinspection PhpHierarchyChecksInspection */
    protected static function instanceOfSelf($instance) {
        $class_name = static::getClass();
        return $instance instanceof $class_name;
    }

    /**
     * @param $init_with
     * @return mixed
     */
    /** @noinspection PhpHierarchyChecksInspection */
    protected static function newSelf($init_with = null) {
        $class_name = static::getClass();
        return new $class_name($init_with);
    }

}
