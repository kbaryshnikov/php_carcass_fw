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
 * method array getRenderArray()
 *
 * @package Carcass\Corelib
 */
trait RenderableTrait {

    protected static $RENDERER_METHOD_NAME_SPRINTF_TEMPLATE = 'render%sTo';

    /** @var \Closure|callable|null */
    protected $renderer = null;

    /** return string|null  default renderer method name, 'render%sTo' method will be called */
    protected function getDefaultRenderer() {
        return null;
    }

    /**
     * Sets renderer. The argument can be:
     *      callable   - renderer callback, function(Corelib\ResultInterface $Result)
     *      string     - renderer method name, 'render%sTo' method will be called,
     *                       e.g. 'Foo' for $this->renderFooTo(Corelib\ResultInterface $Result)
     *      null/empty - reset to default renderer (renderTo() method)
     * @param callable|string|null $renderer
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setRenderer($renderer) {
        if (empty($renderer)) {
            $renderer = null;
        } else {
            if (is_string($renderer)) {
                $renderer = $this->getRendererMethodByString($renderer);
            }
            if (!is_callable($renderer)) {
                throw new \InvalidArgumentException("Invalid renderer");
            }
        }
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * @param $renderer
     * @return callable
     * @throws \InvalidArgumentException
     */
    protected function getRendererMethodByString($renderer) {
        $method = sprintf(static::$RENDERER_METHOD_NAME_SPRINTF_TEMPLATE, ucfirst($renderer));
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Invalid renderer method '$renderer': " . get_class($this) . "::$method() is undefined");
        }
        return [$this, $method];
    }

    /**
     * @param ResultInterface $Result
     * @return $this
     */
    public function renderTo(ResultInterface $Result) {
        $renderer = $this->renderer ?: $this->getDefaultRenderer();
        if ($renderer === null) {
            $this->defaultRenderTo($Result);
        } else {
            if ($renderer instanceof \Closure) {
                /** @noinspection PhpUndefinedMethodInspection */
                $renderer = $renderer->bindTo($this);
            } elseif (is_string($renderer)) {
                $renderer = $this->getRendererMethodByString($renderer);
            }
            $renderer($Result, $this);
        }
        return $this;
    }

    /**
     * Default renderer
     * @param ResultInterface $Result
     */
    protected function defaultRenderTo(ResultInterface $Result) {
        $Result->assign($this->getRenderArray());
    }

    /**
     * @return array
     * @throws \LogicException
     */
    public function getRenderArray() {
        throw new \LogicException("Must be implemented by user of RenderableTrait, " . get_class($this));
        /** @noinspection PhpUnreachableStatementInspection */
        return [];
    }

}
