<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */ // PHPStorm bug: @method not resolved without FQ spec
namespace Carcass\Model;

use Carcass\Query;
use Carcass\Corelib;

/**
 * List query trait
 *
 * @package Carcass\Model
 */
trait ListQueryTrait {
    use Corelib\ArrayObjectTrait;
    use Corelib\RenderableTrait;

    protected static $PAGINATOR_RENDERER = 'ForPaginator';
    protected $default_renderer = null;

    protected $items = [];

    protected $count = null;

    protected $limit = null;
    protected $offset = 0;

    /**
     * @var array of [method => args | :id => [closure]]
     */
    protected $for_each_item_actions = [];

    /**
     * @param $limit
     * @param null $offset
     * @return $this
     */
    public function setLimit($limit, $offset = null) {
        $this->limit = null === $limit ? null : max(1, intval($limit));
        null === $offset or $this->setOffset($offset);
        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function setOffset($offset) {
        $this->offset = max(0, intval($offset));
        return $this;
    }

    /**
     * List importer callback for Query
     * @param $count
     * @param array $items
     * @return $this
     */
    public function importList($count, array $items) {
        $this->count = (int)$count;
        $this->items = $items;
        return $this;
    }

    /**
     * Run $Item->$method($args) for each item.
     * Immediately for existing objects, scheduled for objects created later
     *
     * @param string $method
     * @param array $args
     * @param bool $replace remove previously assigned forEachItem actions
     * @return $this
     */
    public function forEachItemDo($method, array $args = [], $replace = false) {
        if ($replace) {
            $this->for_each_item_actions = [];
        }
        $this->for_each_item_actions[$method] = $args;
        $this->applyForEachLoadedItem($method, $args);
        return $this;
    }

    /**
     * Run $Closure($Item) for each item.
     * Immediately for existing objects, scheduled for objects created later
     *
     * @param \Closure $Closure
     * @param bool $replace remove previously assigned forEachItem actions
     * @return $this
     */
    public function forEachItemDoClosure(\Closure $Closure, $replace = false) {
        return $this->forEachItemDo(':' . Corelib\ObjectTools::toString($Closure), [$Closure], $replace);
    }

    /**
     * Returns the total database count (regardless of limit/offset) of items in database, or null if unknown
     *
     * @return int|null
     */
    public function getCount() {
        return $this->count;
    }

    /**
     * Render for paginator
     *
     * @param bool $enabled
     * @return $this
     */
    public function withPaginator($enabled = true) {
        $this->default_renderer = $enabled ? static::$PAGINATOR_RENDERER : null;
        return $this;
    }

    /**
     * Render without paginator
     *
     * @param bool $bool
     * @return $this
     */
    public function withoutPaginator($bool = true) {
        return $this->withPaginator(!$bool);
    }

    /**
     * @return array
     */
    public function exportArray() {
        $result = [];
        /** @var $Item Base */
        foreach ($this as $idx => $Item) {
            $result[$idx] = $Item->exportArray();
        }
        return $result;
    }

    protected function renderForPaginatorTo(Corelib\ResultInterface $Result) {
        /** @noinspection PhpUndefinedFieldInspection */ // PHPStorm bug
        $Result->count->assign($this->count);
        /** @noinspection PhpUndefinedFieldInspection */
        $Result->limit->assign($this->limit);
        /** @noinspection PhpUndefinedFieldInspection */
        $Result->offset->assign($this->offset);

        /** @noinspection PhpUndefinedFieldInspection */
        /** @noinspection PhpParamsInspection */
        $this->defaultRenderTo($Result->list);
    }

    protected function defaultRenderTo(Corelib\ResultInterface $Result) {
        /** @var $Item Base */
        $idx = 0;
        foreach ($this as $Item) {
            /** @noinspection PhpParamsInspection */
            $Item->renderTo($Result->$idx);
            $idx++;
        }
    }

    protected function getDefaultRenderer() {
        return $this->default_renderer;
    }

    /**
     * @return array
     */
    protected function &getDataArrayPtr() {
        return $this->items;
    }

    /**
     * @param $key
     * @return mixed
     * @throws \OutOfBoundsException
     */
    protected function &getArrayObjectItemByKey($key) {
        if ($this->hasArrayObjectItemByKey($key)) {
            if (!is_object($this->items[$key])) {
                $this->items[$key] = $this->constructItem(is_array($this->items[$key]) ? $this->items[$key] : []);
            }
            return $this->items[$key];
        }
        throw new \OutOfBoundsException("Key is undefined: '$key'");
    }

    /**
     * @param array $data
     * @return Base
     */
    protected function constructItem(array $data) {
        $Item = $this->constructItemModel();
        $data and $Item->fetchFromArray($data);
        $this->applyAllForItem($Item);
        return $Item;
    }

    protected function prepareListQueryDispatcher(Query\BaseDispatcher $QueryDispatcher) {
        $QueryDispatcher->setLimit($this->limit, $this->offset);
        return $QueryDispatcher;
    }

    /**
     * @return Base
     */
    protected function constructItemModel() {
        $class = static::getItemModelClass();
        return new $class;
    }

    /**
     * @throws \LogicException
     * @return string class name
     */
    protected static function getItemModelClass() {
        throw new \LogicException(__METHOD__ . " must be overridden in " . get_called_class());
        /** @noinspection PhpUnreachableStatementInspection */
        return '';
    }

    protected function applyAllForItem($Item) {
        foreach ($this->for_each_item_actions as $method => $args) {
            $this->applyForItem($Item, $method, $args);
        }
    }

    protected function applyForEachLoadedItem($method, array $args = []) {
        foreach ($this->items as $item) {
            if (is_object($item)) {
                $this->applyForItem($item, $method, $args);
            }
        }
    }

    protected function applyForItem($Item, $method, array $args = []) {
        if (substr($method, 0, 1) == ':') {
            $args[0]($Item);
        } else {
            if (!method_exists($Item, $method)) {
                throw new \RuntimeException(get_class($Item) . " has no method '$method'");
            }
            call_user_func_array([$Item, $method], $args);
        }
    }

}