<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * RenderableInterface implementation.
 *
 * User must implement:
 * @method array getRenderArray()
 *
 * @package Carcass\Corelib
 */
trait RenderableTrait {

    /** @var \Closure|callable|null */
    protected $renderer = null;

    /**
     * @param $renderer
     * @return $this
     * @throws \InvalidArgumentException
     */
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

    /**
     * @param ResultInterface $Result
     * @return $this
     */
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
