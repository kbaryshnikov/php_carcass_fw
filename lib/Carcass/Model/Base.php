<?php

namespace Carcass\Model;

use Carcass\Query;
use Carcass\Field;

abstract class Base {

    protected static
        $ModelFieldset = null;

    protected
        $Fieldset = null,
        $Query = null;

    public function __construct() {
        $this->initFieldset();
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
            ->addRules(static::getModelRules())
            ->addFilters(static::getModelFilters())
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

    protected function execute(array $args = []) {
        $this->Query->execute($args);
        $this->fetchResults();
    }

    protected function fetchResults() {
        $this->Fieldset->dynamic(function() {
            $this->Query->execute($args)->sendTo($this->Fieldset);
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
        $this->Fieldset->$key = $value;
    }

}
