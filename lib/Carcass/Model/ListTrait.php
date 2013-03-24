<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Model;

use Carcass\Query;
use Carcass\Corelib;

trait ListTrait {
    use QueryTrait;
    use Corelib\ArrayObjectTrait;
    use Corelib\RenderableTrait;

    protected static $PAGINATOR_RENDERER = 'ForPaginator';
    protected $default_renderer = null;

    protected $items = [];

    protected $count = null;

    protected $limit = null;
    protected $offset = 0;

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
     * @param array $item_data
     * @return $this
     */
    protected function assembleItemModel(array $item_data = []) {
        return static::constructItemModel()->fetchFromArray($item_data);
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
     * return $this
     */
    abstract public function load();

    /**
     * @param array $data
     * @return Base
     */
    protected function constructItem(array $data) {
        $Item = static::constructItemModel();
        $data and $Item->fetchFromArray($data);
        return $Item;
    }

    /**
     * @return \Carcass\Query\Base
     */
    protected function getQuery() {
        if (null === $this->Query) {
            $this->Query = $this->assembleQuery();
        }
        return $this->Query->setLimit($this->limit, $this->offset);
    }

    /**
     * @return Base
     */
    protected static function constructItemModel() {
        $class = static::getItemModelClass();
        return new $class;
    }

    /**
     * @throws \LogicException
     * @return string class name
     */
    protected static function getItemModelClass() {
        throw new \LogicException("Must be overridden in descendants of " . __CLASS__);
        /** @noinspection PhpUnreachableStatementInspection */
        return '';
    }

}