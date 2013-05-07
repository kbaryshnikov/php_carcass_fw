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

    protected $values = [];

    /** @var callable */
    protected $BeforeParse = null;

    /** @var RenderableInterface */
    protected $RenderableObject = null;

    /** @var RenderableInterface[] */
    protected $MergeObjects = [];

    protected $merge_next = false;

    /**
     * Binds a renderable object. $RenderableObject->renderTo($this) will be called, implementation must assign().
     *
     * @param RenderableInterface $RenderableObject
     * @return $this
     */
    public function bind(RenderableInterface $RenderableObject) {
        $this->RenderableObject = $RenderableObject;
        return $this;
    }

    /**
     * Binds an extra renderable object which will be merged to main bound object.
     *
     * @param RenderableInterface $RenderableObject
     * @return $this
     */
    public function bindMerge(RenderableInterface $RenderableObject) {
        array_push($this->MergeObjects, $RenderableObject);
        return $this;
    }

    /**
     * Assigns values. The preferred way is calling this method from inside Renderable::renderTo();
     * massive assign() calls from page controllers usually mean design problems.
     *
     * @param mixed $values
     * @param boolean|null $merge
     * @return $this
     */
    public function assign($values, $merge = null) {
        if (null === $merge) {
            if ($this->merge_next) {
                $merge = true;
                $this->merge_next = false;
            }
        }
        if ($merge) {
            if ($values instanceof ExportableInterface) {
                $values = $values->exportArray();
            }
            if (is_array($values)) {
                ArrayTools::mergeInto($this->values, $values, [], true);
                return $this;
            }
        }
        $this->values = $values;
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
            $this->merge_next = true;
            $e = null;
            try {
                $RenderableObject->renderTo($this);
            } catch (\Exception $e) {
                // pass
            }
            // finally
            $this->merge_next = false;
            if ($e) {
                throw $e;
            }
        }
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
        if (!array_key_exists($k, $this->values)) {
            $this->values[$k] = $this->constructSelfSubitem();
        }
        return $this->values[$k];
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

}
