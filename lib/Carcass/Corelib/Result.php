<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Base ResultInterface implementation
 *
 * @package Carcass\Corelib
 */
class Result implements ResultInterface {

    /**
     * @var Result[]
     */
    protected $subresults = [];

    protected $values = [];

    /** @var callable */
    protected $BeforeParse = null;

    /** @var RenderableInterface */
    protected $RenderableObject = null;

    /** @var RenderableInterface[] */
    protected $MergeObjects = [];

    /**
     * Binds a renderable object. $RenderableObject->renderTo($this) will be called, implementation must assign().
     *
     * @param RenderableInterface|array $RenderableObject
     * @return $this
     */
    public function bind($RenderableObject) {
        $this->RenderableObject = $this->toBindable($RenderableObject);
        return $this;
    }

    /**
     * Binds an extra renderable object which will be merged to main bound object.
     *
     * @param RenderableInterface|array $RenderableObject
     * @return $this
     */
    public function bindMerge($RenderableObject) {
        array_push($this->MergeObjects, $this->toBindable($RenderableObject));
        return $this;
    }

    /**
     * Assigns values. The preferred way is calling this method from inside Renderable::renderTo();
     * massive assign() calls from page controllers usually mean design problems.
     *
     * @param mixed $values
     * @return $this
     */
    public function assign($values) {
        $this->assignValue($values);
        return $this;
    }

    /**
     * Assigns raw value, without any post-processing.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function assignRaw($key, $value) {
        $this->values[$key] = $value;
        return $this;
    }

    /**
     * Fetches array. Passes RenderableObjects and nested results recursively.
     * @throws \Exception
     * @return array
     */
    public function exportArray() {
        if (null !== $this->RenderableObject) {
            $this->RenderableObject->renderTo($this);
            $this->RenderableObject = null; // we should have assign()-ed values now, so should not call renderTo() twice.
        }
        while ($RenderableObject = array_pop($this->MergeObjects)) { // handle bindMerge'd objects
            $RenderableObject->renderTo($this);
        }
        $this->exportSubresults();
        if (ArrayTools::isTraversable($this->values)) {
            $result = array();
            foreach ($this->values as $key => $values) {
                $result[$key] = $this->getValuesFrom($values);
            }
        } else {
            $result = $this->getValuesFrom($this->values);
        }
        return $result;
    }

    /**
     * __get magic is used to autocreate nested result objects, so $Result->OuterBlock->NestedBlock->bind() calls are possible.
     *
     * @param string $k
     * @return self
     */
    public function __get($k) {
        if (!array_key_exists($k, $this->subresults)) {
            $this->subresults[$k] = $this->constructSelfSubitem();
        }
        return $this->subresults[$k];
    }

    /**
     * __call magic is used to set a nested result object to another Result instance.
     * Usage: $Result->block_name(new FancyResult)
     *
     * @param string $k
     * @param array $args of (Result $Result)
     * @throws \InvalidArgumentException
     * @return ResultInterface
     */
    public function __call($k, $args) {
        if (count($args) != 1) {
            throw new \InvalidArgumentException('1 argument expected');
        }
        $Subresult = array_shift($args);
        if (!$Subresult instanceof ResultInterface) {
            throw new \InvalidArgumentException('Result instance expected');
        }
        $this->values[$k] = $Subresult;
        return $Subresult;
    }

    /**
     * @param ResponseInterface $Response
     * @return $this
     */
    public function displayTo(ResponseInterface $Response) {
        $Response->writeLn(print_r($this->exportArray(), true));
        return $this;
    }

    /**
     * @param $values
     * @return array
     */
    protected function getValuesFrom($values) {
        return $values instanceof ExportableInterface ? $values->exportArray() : $values;
    }

    /**
     * @return static
     */
    protected function constructSelfSubitem() {
        return new static;
    }

    /**
     * @param RenderableInterface|array $in
     * @return RenderableInterface
     * @throws \InvalidArgumentException
     */
    protected function toBindable($in) {
        if (is_array($in)) {
            $in = new Hash($in);
        }
        if (!$in instanceof RenderableInterface) {
            throw new \InvalidArgumentException("array or instanceof RenderableInterface expected");
        }
        return $in;
    }

    protected function exportSubresults() {
        foreach ($this->subresults as $key => $Result) {
            $this->assignSubvalue($key, $Result->exportArray());
            unset($this->subresults[$key]);
        }
    }

    protected function assignValue($value, $replace = true) {
        if (!is_array($value)) {
            $this->values = $value;
        } else {
            if (!is_array($this->values)) {
                $this->values = [];
            }
            ArrayTools::mergeInto($this->values, $value, [], $replace);
        }
    }

    protected function assignSubvalue($key, $value, $replace = true) {
        if (!is_array($value)) {
            $this->values[$key] = $value;
        } else {
            if (!isset($this->values[$key]) || !is_array($this->values[$key])) {
                $this->values[$key] = [];
            }
            ArrayTools::mergeInto($this->values[$key], $value, [], $replace);
        }
    }

}
