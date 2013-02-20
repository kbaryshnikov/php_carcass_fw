<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

use Closure;
use InvalidArgumentException;

/**
 * Simple pseudo-IoC implementation.
 *
 * Below are a few commonly used definitions just for IDE hints.
 * @method \Carcass\Config\ItemInterface getConfigReader()
 * @method \Carcass\Connection\Manager getConnectionManager()
 * @method \Carcass\Log\Dispatcher getLogger()
 * @method \Carcass\DevTools\Debugger getDebugger()
 * @method \Carcass\Application\PathManager getPathManager()
 * @method \Carcass\Application\Web_Router_Interface getRouter()
 * @method \Carcass\Corelib\Request getRequest()
 *
 * @package Carcass\Corelib
 */
class Injector {

    protected $registry = [];

    /**
     * Reuse the instance created by $ctor
     *
     * @param callable $ctor
     * @return callable
     */
    public function reuse(Closure $ctor) {
        return function($self) use ($ctor) {
            static $instance = null;
            return null === $instance ? $instance = $ctor($self) : $instance;
        };
    }

    /**
     * Register a closure as closure itself, not a ctor function
     *
     * @param string $name
     * @param callable $value
     */
    public function setClosure($name, Closure $value) {
        $this->$name = function() use ($value) {
            return $value;
        };
    }

    /**
     * Register a value
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->registry[$name] = $value;
    }

    /**
     * Get the depencency by its name
     *
     * @param $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name) {
        if (!array_key_exists($name, $this->registry)) {
            throw new InvalidArgumentException("Undefined dependency: '$name'");
        }
        return $this->registry[$name] instanceof Closure ? $this->registry[$name]($this) : $this->registry[$name];
    }

    /**
     * Call the ctor function for $name dependency with $arguments
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __call($name, array $arguments) {
        if (!array_key_exists($name, $this->registry)) {
            throw new InvalidArgumentException("Undefined dependency: '$name'");
        }
        if (!$this->registry[$name] instanceof Closure) {
            throw new InvalidArgumentException("Dependency is not constructable: '$name'");
        }
        array_unshift($arguments, $this);
        return call_user_func_array($this->registry[$name], $arguments);
    }

}
