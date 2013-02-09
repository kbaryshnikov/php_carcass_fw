<?php

namespace Carcass\Model;

use Carcass\Query;
use Carcass\Field;
use Carcass\Corelib;

abstract class Base implements Corelib\DataReceiverInterface, Corelib\ExportableInterface, Corelib\RenderableInterface {
    use Corelib\RenderableTrait;

    protected static
        $ModelFieldset = null;

    protected
        $Fieldset = null,
        $Query = null;

    public function __construct() {
        $this->initFieldset();
    }

    public function validate() {
        return $this->Fieldset->validate();
    }

    public function getErrors() {
        return $this->Fieldset->getError();
    }

    protected function initFieldset() {
        $this->Fieldset = clone static::getModelFieldset();
    }

    protected static function getModelFieldset() {
        if (null === static::$ModelFieldset) {
            static::$ModelFieldset = static::assembleModelFieldset();
        }
        return static::$ModelFieldset;
    }

    protected static function assembleModelFieldset() {
        return Field\Set::constructDynamic()
            ->addFields(static::getModelFields())
            ->setRules(static::getModelRules())
            ->setFilters(static::getModelFilters())
            ->setDynamic(false);
    }

    protected static function getModelFields() {
        return [];
    }

    protected static function getModelRules() {
        return [];
    }

    protected static function getModelFilters() {
        return [];
    }

    protected function doFetch($query, array $args) {
        $this->getQuery()->fetchRow($query);
        $this->executeQuery($args);
    }

    protected function doInsert($query, array $args = []) {
        if (!$this->validate()) {
            return false;
        }
        return $this->getQuery()->insert($query, $args + $this->exportArray());
    }

    protected function doModify($query, array $args = []) {
        if (!$this->validate()) {
            return false;
        }
        return $this->getQuery()->modify($query, $args + $this->exportArray());
    }

    protected function executeQuery(array $args = []) {
        $this->getQuery()->execute($args);
        $this->fetchResults();
    }

    protected function fetchResults() {
        $this->Fieldset->dynamic(function() {
            $this->Fieldset->clean();
            $this->Query->sendTo($this->Fieldset);
        });
    }

    protected function getQuery() {
        if (null === $this->Query) {
            $this->Query = $this->assembleQuery();
        }
        return $this->Query;
    }

    protected function assembleQuery() {
        return new Query\Base;
    }

    public function getFieldset() {
        return $this->Fieldset;
    }

    public function __get($key) {
        return $this->Fieldset->$key;
    }

    public function __set($key, $value) {
        $this->Fieldset->set($key, $value);
    }

    public function fetchFrom(\Traversable $Source) {
        $this->Fieldset->fetchFrom($Source);
        return $this;
    }

    public function fetchFromArray(array $source) {
        $this->Fieldset->fetchFromArray($source);
        return $this;
    }

    public function exportArray() {
        return $this->Fieldset->exportArray();
    }

    public function getRenderArray() {
        return $this->Fieldset->exportArray();
    }

}
