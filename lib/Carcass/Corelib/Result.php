<?php

namespace Carcass\Corelib;

class Result implements ResultInterface {

    protected
        $values = array(),
        $BeforeParse = null,
        $RenderableObject = null;

    /**
     * Binds a renderable object. $RenderableObject->renderTo($this) will be called, implementation must assign().
     * 
     * @param RenderableInterface $RenderableObject 
     * @return self
     */
    public function bind(RenderableInterface $RenderableObject) {
        $this->RenderableObject = $RenderableObject;
        return $this;
    }

    /**
     * Assigns values. The preferred way is calling this method from inside Renderable::renderTo();
     * massive assign() calls from page controllers usually mean design problems.
     * 
     * @param mixed $values 
     * @return self
     */
    public function assign($values) {
        $this->values = $values;
        return $this;
    }

    /**
     * Fetches array. Passes RenderableObjects and nested results recursively.
     * @return array
     */
    public function exportArray() {
        if (null !== $this->RenderableObject) {
            $this->RenderableObject->renderTo($this);
            $this->RenderableObject = null; // we should have assign()-ed values now, so should not call renderTo() twice.
        }
        if (!is_array($this->values) && !($this->values instanceof Traversable)) {
            $result = $this->getValuesFrom($this->values);
        } else {
            $result = array();
            foreach ($this->values as $key => $values) {
                $result[$key] = $this->getValuesFrom($values);
            }
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
     * @return self
     */
    public function __call($k, $args) {
        if (count($args) != 1) {
            throw new InvalidArgumentException('1 argument expected');
        }
        $Subresult = array_shift($args);
        if (!$Subresult instanceof ResultInterface) {
            throw new InvalidArgumentException('Result instance expected');
        }
        $this->values[$k] = $Subresult;
        return $Subresult;
    }

    public function displayTo(ResponseInterface $Response) {
        $Response->writeLn(print_r($this->exportArray(), true));
    }

    protected function getValuesFrom($values) {
        return $values instanceof ExportableInterface ? $values->exportArray() : $values;
    }

    protected function constructSelfSubitem() {
        return new static;
    }

}
