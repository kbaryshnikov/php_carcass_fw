<?php

namespace Carcass\Corelib;

class View implements ViewInterface {

    protected
        $values = array(),
        $BeforeParse = NULL,
        $RenderableObject = NULL;

    /**
     * Binds a renderable object. $RenderableObject->renderTo($this) will be called, implementation must assign().
     * 
     * @param Carcass_Abstract_RenderableInterface $RenderableObject 
     * @return void
     */
    public function bind(Carcass_Abstract_RenderableInterface $RenderableObject) {
        $this->RenderableObject = $RenderableObject;
    }

    /**
     * Assigns values. The preferred way is calling this method from inside Renderable::renderTo();
     * assign() calls from page dispatchers usually mean design problems.
     * 
     * @param mixed $values 
     * @return void
     */
    public function assign($values) {
        $this->values = $values;
    }

    /**
     * Fetches array. Passes RenderableObjects and nested views recursively.
     * 
     * @return array situable for $Template->parse( $View->exportArray() )
     */
    public function exportArray() {
        if (NULL !== $this->RenderableObject) {
            $this->RenderableObject->renderTo($this);
            $this->RenderableObject = NULL; // we should have assign()-ed values now, so should not call renderTo() twice.
        }
        if (!is_array($this->values) && !($this->values instanceof Traversable)) {
            $result = $this->getValuesFrom($this->values);
        } else {
            $result = array();
            foreach ($this->values as $key => $values) {
                $result[$key] = $this->getValuesFrom($values);
            }
        }
        $this->beforeParse($result);
        return $result;
    }

    public function parseTemplate(Carcass_Template_Interface $Template) {
        return $Template->parse($this->exportArray());
    }

    /**
     * __get magic is used to autocreate nested view objects, so $View->OuterBlock->NestedBlock->bind() calls are possible.
     * 
     * @param string $k 
     * @return Carcass_View
     */
    public function __get($k) {
        if (!array_key_exists($k, $this->values)) {
            $this->values[$k] = $this->constructSelfSubitem();
        }
        return $this->values[$k];
    }

    /**
     * __call magic is used to set a nested view object to another View instance.
     * Usage: $View->block_name(new Fancy_Carcass_View)
     * 
     * @param string $k
     * @param array $args of (Carcass_View $View)
     * @return Carcass_View
     */
    public function __call($k, $args) {
        if (count($args) != 1) {
            throw new InvalidArgumentException('1 argument expected');
        }
        $Subview = array_shift($args);
        if (!$Subview instanceof self) {
            throw new InvalidArgumentException('Carcass_View instance expected');
        }
        $this->values[$k] = $Subview;
        return $Subview;
    }

    public function onParse(Closure $Closure = NULL) {
        $this->BeforeParse = $Closure;
    }

    protected function getValuesFrom($values) {
        return $values instanceof Carcass_Abstract_ExportableInterface ? $values->exportArray() : $values;
    }

    protected function beforeParse(&$data) {
        if ($this->BeforeParse) {
            $BeforeParse = $this->BeforeParse;
            $BeforeParse(&$data);
        }
    }

    protected function constructSelfSubitem() {
        return new static;
    }

    public function changeToSubclass($subclass) {
        // ugly hack. Nope... two ugly hacks
        class_exists($subclass);
        if (get_class($this) == $subclass) {
            return $this;
        }
        ${'th'.'is'} = unserialize(
            preg_replace(
                '/^O:[0-9]+:"[^"]+":/',
                'O:' . strlen($subclass) . ':"' . $subclass . '":',
                serialize($this)
            )
        );
        return $this;
    }

}
