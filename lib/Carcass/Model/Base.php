<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Model;

use Carcass\Query;
use Carcass\Field;
use Carcass\Corelib;

/**
 * Base Model
 * @package Carcass\Model
 */
abstract class Base implements Corelib\DatasourceInterface, Corelib\DataReceiverInterface, Corelib\ImportableInterface, Corelib\ExportableInterface, Corelib\RenderableInterface, Corelib\FilterableDatasourceInterface,  Query\ItemReceiverInterface {
    use QueryTrait;
    use Corelib\RenderableTrait;

    /**
     * @var Field\Set|null
     */
    protected static $ModelFieldset = null;

    /**
     * @var Field\Set|null
     */
    protected $Fieldset = null;

    public function __construct() {
        $this->initFieldset();
        $this->import($this->getModelFieldDefaultValues());
    }

    /**
     * @return bool
     */
    public function validate() {
        return $this->Fieldset->validate();
    }

    /**
     * @return mixed
     */
    public function getErrors() {
        return $this->Fieldset->getError();
    }

    /**
     * @return void
     */
    protected function initFieldset() {
        $this->Fieldset = clone $this->getModelFieldset();
    }

    /**
     * @return Field\Set
     */
    protected function getModelFieldset() {
        return $this->constructFieldsetInstance()
            ->setDynamic(true)
            ->addFields(static::getModelFields())
            ->setRules(static::getModelRules())
            ->setFilters(static::getModelFilters())
            ->setDynamic(false);
    }

    /**
     * @return Field\Set
     */
    protected function constructFieldsetInstance() {
        return new Field\Set;
    }

    /**
     * @return array
     */
    protected function getModelFieldDefaultValues() {
        return [];
    }

    /**
     * @return array
     */
    protected static function getModelFields() {
        return [];
    }

    /**
     * @return array
     */
    protected static function getModelRules() {
        return [];
    }

    /**
     * @return array
     */
    protected static function getModelFilters() {
        return [];
    }

    /**
     * @param string $query
     * @param array $args
     * @param callable $convert_fn
     */
    protected function doFetch($query, array $args, callable $convert_fn = null) {
        $this->getQueryDispatcher()->setResultConverter($convert_fn)->fetchRow($query);
        $this->executeQuery($args);
    }

    /**
     * @param string $query
     * @param array $args
     * @return bool|null
     */
    protected function doInsert($query, array $args = []) {
        if (!$this->validate()) {
            return false;
        }
        return $this->getQueryDispatcher()->insert($query, $args + $this->exportArray());
    }

    /**
     * @param string $query
     * @param array $args
     * @return bool|mixed
     */
    protected function doModify($query, array $args = []) {
        if (!$this->validate()) {
            return false;
        }
        return $this->getQueryDispatcher()->modify($query, $args + $this->exportArray());
    }

    /**
     * @param array $args
     */
    protected function executeQuery(array $args = []) {
        $this->getQueryDispatcher()->execute($args);
        $this->fetchResults();
    }

    /**
     * return void
     */
    protected function fetchResults() {
        $this->getQueryDispatcher()->sendTo($this);
    }

    /**
     * @return Field\Set|null
     */
    public function getFieldset() {
        return $this->Fieldset;
    }

    public function importItem(array $data = null) {
        $this->Fieldset->dynamic(
            function () use ($data) {
                $this->Fieldset->clean();
                $this->import($data);
            }
        );
        return $this;
    }

    /**
     * @return $this
     */
    public function clean() {
        $this->Fieldset->dynamic(
            function () {
                $this->Fieldset->clean();
            }
        );
        return $this;
    }

    /**
     * @param $key
     * @return Field\FieldInterface|mixed
     */
    public function __get($key) {
        return $this->Fieldset->$key;
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function __set($key, $value) {
        $this->Fieldset->set($key, $value);
    }

    /**
     * @param mixed $key
     * @param mixed $default_value
     * @return mixed|null
     */
    public function get($key, $default_value = null) {
        return $this->Fieldset->getFieldValue($key, $default_value);
    }

    /**
     * @param $field_name
     * @return Field\FieldInterface
     */
    public function getField($field_name) {
        return $this->Fieldset->getField($field_name);
    }

    /**
     * @param $field_name
     * @param $error
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setError($field_name, $error) {
        $this->Fieldset->setFieldError($field_name, $error);
        return $this;
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function has($key) {
        return $this->Fieldset->has($key);
    }

    /**
     * @param \Traversable $Source
     * @param bool $no_overwrite
     * @return $this
     */
    public function fetchFrom(\Traversable $Source, $no_overwrite = false) {
        $this->Fieldset->fetchFrom($Source, $no_overwrite);
        return $this;
    }

    /**
     * @param array $source
     * @param bool $no_overwrite
     * @return $this
     */
    public function fetchFromArray(array $source, $no_overwrite = false) {
        $this->Fieldset->fetchFromArray($source, $no_overwrite);
        return $this;
    }

    /**
     * @param \Traversable|array $data
     * @param bool $no_overwrite
     * @return $this
     */
    public function import($data, $no_overwrite = false) {
        $this->Fieldset->import($data, $no_overwrite);
        return $this;
    }

    /**
     * @return array
     */
    public function exportArray() {
        return $this->Fieldset->exportArray();
    }

    /**
     * @param string $path dot-separated
     * @param mixed $default_value
     * @return mixed
     */
    public function getPath($path, $default_value = null) {
        return $this->Fieldset->getPath($path, $default_value);
    }

    /**
     * @return array
     */
    public function getRenderArray() {
        return $this->Fieldset->getRenderArray();
    }

    /**
     * @param array $allowed_fields
     * @return array
     */
    public function exportFilteredArray(array $allowed_fields) {
        return $this->Fieldset->exportFilteredArray($allowed_fields);
    }

    /**
     * @param array $allowed_fields
     * @return Corelib\Hash
     */
    public function exportFilteredHash(array $allowed_fields) {
        return $this->Fieldset->exportFilteredHash($allowed_fields);
    }

    protected function assembleQueryDispatcher() {
        return new Query\BaseDispatcher;
    }

    protected function prepareQueryDispatcher(Query\BaseDispatcher $QueryDispatcher) {
        return $QueryDispatcher;
    }

}
