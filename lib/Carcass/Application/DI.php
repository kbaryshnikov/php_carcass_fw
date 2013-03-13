<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
namespace Carcass\Application;

use Carcass\Corelib;

/**
 * Application injector singleton.
 *
 * Below are IDE hints for commonly used calls. It does not mean that it's somewhat limited to, just IDE hints.
 * See __callStatic() for details.
 *
 * @method static \Carcass\Config\ItemInterface getConfigReader()
 * @method static \Carcass\Connection\Manager getConnectionManager()
 * @method static \Carcass\Log\Dispatcher getLogger()
 * @method static \Carcass\DevTools\Debugger getDebugger()
 * @method static \Carcass\Application\PathManager getPathManager()
 * @method static \Carcass\Application\Web_Router_Interface getRouter()
 * @method static \Carcass\Corelib\Request getRequest()
 *
 * @package Carcass\Application
 */
class DI {

    private static $instance = null;

    private function __construct() {
        // pass
    }

    private function __clone() {
        // pass
    }

    /**
     * @return bool
     */
    public static function isEnabled() {
        return self::$instance !== null;
    }

    /**
     * Returns NullObject if was not configured, so all DI:: calls are test-safe.
     * @return \Carcass\Corelib\DIContainer
     */
    public static function getInstance() {
        if (null === self::$instance) {
            return new Corelib\NullObject;
        }
        return self::$instance;
    }

    /**
     * @param \Carcass\Corelib\DIContainer $Injector
     * @return \Carcass\Corelib\DIContainer
     */
    public static function setInstance(Corelib\DIContainer $Injector = null) {
        return self::$instance = $Injector;
    }

    /**
     * @param string $method must start with 'get' followed by dependency name
     * @param array $args If empty, return the dependency by name. Otherwise, call the injector on the dependency name with $args.
     * @return mixed If no args, depencency object is returned. If there are args, injector call result is returned.
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, array $args) {
        if (substr($method, 0, 3) == 'get') {
            $dep_name = substr($method, 3);
            if (empty($args)) {
                return static::getInstance()->$dep_name;
            } else {
                return call_user_func_array([static::getInstance(), $dep_name], $args);
            }
        }
        throw new \BadMethodCallException("Invalid method call: '$method'");
    }

}
