<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

use Carcass\Application\PathManager;
use Carcass\Config\ItemInterface;
use Carcass\Connection\Manager;
use Carcass\DevTools\Debugger;
use Carcass\Log\Dispatcher as LogDispatcher;
use Carcass\Mail\Dispatcher as MailDispatcher;
use Closure;
use InvalidArgumentException;

/**
 * Simple pseudo-IoC implementation.
 *
 * Below are a few commonly used definitions just for IDE hints.
 * @property ItemInterface ConfigReader
 * @method ItemInterface getConfigReader()
 * @property Manager ConnectionManager
 * @method Manager getConnectionManager()
 * @property LogDispatcher Logger
 * @method LogDispatcher getLogger()
 * @property Debugger Debugger
 * @method Debugger getDebugger()
 * @property PathManager PathManager
 * @method PathManager getPathManager()
 * @property MailDispatcher MailDispatcher
 * @method MailDispatcher getMailDispatcher()
 *
 * @package Carcass\Corelib
 */
class DIContainer {

    protected $registry = [];

    /**
     * Reuse the instance created by $ctor
     *
     * @param Closure $ctor
     * @return Closure
     */
    public function reuse(Closure $ctor) {
        return function($self) use ($ctor) {
            static $instance = null;
            return null === $instance ? $instance = $ctor($self) : $instance;
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
     * Returns a closure wrapped for usage as a dependency,
     * to distinguish from a ctor-closure.
     *
     * @param Closure $value
     * @return Closure
     */
    public function wrapClosure(Closure $value) {
        return function() use ($value) {
            return $value;
        };
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
