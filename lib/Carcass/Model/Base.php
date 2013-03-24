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
abstract class Base implements Corelib\DataReceiverInterface, Corelib\ExportableInterface, Corelib\RenderableInterface {
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
    }

    /**
     * @return mixed
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

    protected function initFieldset() {
        $this->Fieldset = clone static::getModelFieldset();
    }

    /**
     * @return Field\Set
     */
    protected static function getModelFieldset() {
        if (null === static::$ModelFieldset) {
            static::$ModelFieldset = static::assembleModelFieldset();
        }
        return static::$ModelFieldset;
    }

    /**
     * @return $this
     */
    protected static function assembleModelFieldset() {
        return Field\Set::constructDynamic()
            ->addFields(static::getModelFields())
            ->setRules(static::getModelRules())
            ->setFilters(static::getModelFilters())
            ->setDynamic(false);
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
        $this->getQuery()->setResultConverter($convert_fn)->fetchRow($query);
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
        return $this->getQuery()->insert($query, $args + $this->exportArray());
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
        return $this->getQuery()->modify($query, $args + $this->exportArray());
    }

    /**
     * @param array $args
     */
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


    /**
     * @return Field\Set|null
     */
    public function getFieldset() {
        return $this->Fieldset;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key) {
        return $this->Fieldset->$key;
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value) {
        $this->Fieldset->set($key, $value);
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
     * @return mixed
     */
    public function exportArray() {
        return $this->Fieldset->exportArray();
    }

    /**
     * @return mixed
     */
    public function getRenderArray() {
        return $this->Fieldset->exportArray();
    }

}
