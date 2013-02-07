<?php

namespace Carcass\Corelib;

trait RenderableTrait {

    // getRenderArray() must be implemented

    protected $renderer = null;

    public function setRenderer($renderer) {
        if (empty($renderer)) {
            $renderer = null;
        } else {
            if (is_string($renderer)) {
                if (!method_exists($this, $renderer)) {
                    throw new \InvalidArgumentException("Invalid renderer method: '$renderer'");
                }
                $renderer = [ $this, $renderer ];
            }
            if (!is_callable($renderer)) {
                throw new \InvalidArgumentException("Invalid renderer");
            }
        }
        $this->renderer = $renderer;
        return $this;
    }

    public function renderTo(ResultInterface $Result) {
        if ($this->renderer === null) {
            $Result->assign($this->getRenderArray());
        } else {
            $renderer = $this->renderer;
            if ($renderer instanceof \Closure) {
                $renderer = $renderer->bindTo($this);
            }
            $renderer($Result, $this);
        }
        return $this;
    }

}
