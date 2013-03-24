<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;
use Carcass\Config;
use Carcass\Log;
use Carcass\DevTools;
use Carcass\Connection;

/**
 * Application instance singleton
 * @package Carcass\Application
 */
class Instance {

    protected static $env_defaults = [
        'configuration_name' => null,
        'lib_path'           => [],
        'run_mode'           => null,
        'namespace'          => null,
    ];

    protected static $opt_defaults = [
        'env_file'   => 'env.php',
        'etc_file'   => 'etc.php',
        'config_dir' => 'config/',
    ];

    protected static $debug_reporter_defaults = [
        'cli' => 'console',
        'web' => 'firephp',
    ];

    protected $app_root;
    protected $app_env;
    protected $options;

    /**
     * @var \Carcass\Application\Autoloader
     */
    protected $Autoloader;

    /**
     * @var \Carcass\Config\ItemInterface
     */
    protected $ConfigReader;
    /**
     * @var \Carcass\Log\Dispatcher
     */
    protected $Logger;
    /**
     * @var \Carcass\DevTools\Debugger
     */
    protected $Debugger;
    /**
     * @var \Carcass\Application\PathManager
     */
    protected $PathManager;
    /**
     * @var \Carcass\Corelib\DIContainer
     */
    protected $DI;
    /**
     * @var \Carcass\Connection\Manager
     */
    protected $ConnectionManager;
    /**
     * @var \Carcass\Corelib\Crypter
     */
    protected $Crypter = null;

    /**
     * @var bool
     */
    protected $finalized = false;

    /**
     * @var DevTools\Timer
     */
    protected $Timer = null;

    /**
     * @var \Carcass\Application\Instance
     */
    private static $instance = null;

    /**
     * Initialize and run the application.
     * @param string $app_root Application root
     * @param array $overrides Application settings overrides
     * @return mixed Application execution result
     */
    public static function run($app_root, array $overrides = []) {
        return static::init($app_root, $overrides)->execute();
    }

    /**
     * Initialize the application.
     * @param string $app_root Application root
     * @param array $overrides Application settings overrides
     * @return Instance Application instance
     */
    public static function init($app_root, array $overrides = []) {
        return new static($app_root, $overrides);
    }

    /**
     * Execute the applicaton
     * @return $this
     */
    public function execute() {
        $this->DI->FrontController->run();
        $this->finalize();
        return $this;
    }

    /**
     * Returns the application environment variable value
     * @param string $key Environment variable name
     * @param null $default_value
     * @return mixed
     */
    public static function getEnv($key, $default_value = null) {
        return array_key_exists($key, static::$instance->app_env) ? static::$instance->app_env[$key] : $default_value;
    }

    /**
     * Returns the fully-qualified class name for current application namespace,
     * the application namespace is set by the 'namespace' application environment variable.
     * @param string $class_name Relative application class name
     * @return string
     */
    public static function getFqClassName($class_name) {
        if (substr($class_name, 0, 1) == '\\') {
            return $class_name;
        }
        $app_namespace = static::getEnv('namespace');
        return $app_namespace ? "$app_namespace\\$class_name" : "\\$class_name";
    }

    /**
     * Destroy the application instance. Can be useful for tests.
     */
    public static function destroy() {
        if (!static::$instance) {
            return;
        }
        DI::setInstance(null);
        static::$instance = null;
    }

    protected function __construct($app_root, array $overrides = []) {
        if (static::$instance) {
            throw new \LogicException('Application already created');
        }
        static::$instance = $this;
        $this->app_root = static::fixPath($app_root);
        $this->options = $overrides + static::$opt_defaults;
        $this->bootstrap();
    }

    protected function bootstrap() {
        $this->setupErrorHandler();
        $this->loadApplicationConfiguration();
        $this->setupRunMode();
        $this->setupAutoloader();
        $this->startTimer();
        $this->setupConfigReader();
        $this->setupLogger();
        $this->setupDebugger();
        $this->setupPathManager();
        $this->setupDependencies();
    }

    protected function finalize() {
        if ($this->finalized) {
            return;
        }
        if ($this->Debugger && $this->Debugger->isEnabled()) {
            if ($this->Timer) {
                $this->Debugger->registerTimer('application', $this->Timer);
                $this->Timer->stop();
            }
            $this->Debugger->finalize();
        }
        if ($this->DI->Response && $this->DI->Response->isBuffering()) {
            $this->DI->Response->commit();
        }
        $this->finalized = true;
    }

    public function __destruct() {
        $this->finalize();
    }

    protected function startTimer() {
        $this->Timer = (new DevTools\Timer('Total execution time'))->start();
    }

    protected function setupDependencies() {
        $this->DI = DI::setInstance(new Corelib\DIContainer);

        $dep_config = $this->ConfigReader->exportArrayFrom('application.bootstrap.' . $this->app_env['run_mode'], []);
        $dep_map = $this->prefixNamespaces(isset($dep_config['map']) && is_array($dep_config['map']) ? $dep_config['map'] : []);

        if (isset($dep_config['fn']) && $dep_config['fn'] instanceof \Closure) {
            $setupFn = $dep_config['fn'];
        } else {
            $setupFn = [$this, 'setupDependencies' . $this->app_env['run_mode']];
        }

        $this->DI->dep_map = $dep_map;
        $this->DI->app_env = $this->app_env;

        $this->DI->Namespace = isset($this->app_env['namespace']) ? $this->app_env['namespace'] : '\\';

        $this->DI->ConfigReader = $this->ConfigReader;
        $this->DI->PathManager = $this->PathManager;
        $this->DI->Debugger = $this->Debugger;
        $this->DI->Logger = $this->Logger;

        $this->DI->Crypter = $this->DI->reuse(
            isset($dep_map['CrypterFn']) ? $dep_map['CrypterFn'] : function (Corelib\DIContainer $I) {
                $class = (isset($I->dep_map['Crypter']) ? $I->dep_map['Crypter'] : '\Carcass\Corelib\Crypter');
                $crypter_settings = $I->ConfigReader->getPath('application.secret', null);
                if (is_object($crypter_settings)) {
                    $crypter_settings = $crypter_settings->exportArray();
                }
                $this->Crypter = new $class($crypter_settings);
            }
        );

        $this->DI->EventDispatcher = $this->DI->reuse(
            isset($dep_map['EventDispatcherFn']) ? $dep_map['EventDispatcherFn'] : function (Corelib\DIContainer $I) {
                $class = (isset($I->dep_map['EventDispatcher']) ? $I->dep_map['EventDispatcher'] : '\Carcass\Event\Dispatcher');
                return new $class;
            }
        );

        $this->DI->ConnectionManager = $this->DI->reuse(
            isset($dep_map['ConnectionManagerFn']) ? $dep_map['ConnectionManagerFn'] : function (Corelib\DIContainer $I) {
                /** @var \Carcass\Connection\Manager $ConnectionManager */
                $class = (isset($I->dep_map['ConnectionManager']) ? $I->dep_map['ConnectionManager'] : '\Carcass\Connection\Manager');
                $ConnectionManager = new $class;
                return $ConnectionManager->registerTypes($I->ConfigReader->exportArrayFrom('connections.types', []));
            }
        );

        if (is_callable($setupFn)) {
            $setupFn($this->DI, $dep_map);
        } else {
            throw new \LogicException("Cannot setupDependencies() for {$this->app_env['run_mode']} mode: no setup function "
                . "is defined in configuration, and mode is not supported internally");
        }

        $this->setupApplicationDependencies();
    }

    protected function setupDependenciesCli($Injector, array $dep_map) {
        $Injector->Request = $this->DI->reuse(
            isset($dep_map['RequestFn']) ? $dep_map['RequestFn'] : function (Corelib\DIContainer $I) {
                /** @var RequestBuilderInterface $class */
                $class = (isset($I->dep_map['RequestBuilder']) ? $I->dep_map['RequestBuilder'] : '\Carcass\Application\Cli_RequestBuilder');
                return $class::assembleRequest($this->app_env);
            }
        );

        $Injector->Response = $this->DI->reuse(
            isset($dep_map['ResponseFn']) ? $dep_map['ResponseFn'] : function (Corelib\DIContainer $I) {
                $class = (isset($I->dep_map['Response']) ? $I->dep_map['Response'] : '\Carcass\Application\Cli_Response');
                return new $class;
            }
        );

        $Injector->Router = $this->DI->reuse(
            isset($dep_map['RouterFn']) ? $dep_map['RouterFn'] : function (Corelib\DIContainer $I) {
                $class = (isset($I->dep_map['Router']) ? $I->dep_map['Router'] : '\Carcass\Application\Cli_Router');
                return new $class;
            }
        );

        $Injector->FrontController = isset($dep_map['FrontControllerFn']) ? $dep_map['FrontControllerFn'] : function (Corelib\DIContainer $I) {
            $class = (isset($I->dep_map['FrontController']) ? $I->dep_map['FrontController'] : '\Carcass\Application\Cli_FrontController');
            return new $class($I->Request, $I->Response, $I->Router);
        };
    }

    protected function setupDependenciesWeb($Injector, array $dep_map) {
        $Injector->Request = $this->DI->reuse(
            isset($dep_map['RequestFn']) ? $dep_map['RequestFn'] : function (Corelib\DIContainer $I) {
                /** @var RequestBuilderInterface $class */
                $class = (isset($I->dep_map['RequestBuilder']) ? $I->dep_map['RequestBuilder'] : '\Carcass\Application\Web_RequestBuilder');
                return $class::assembleRequest($this->app_env);
            }
        );

        $Injector->Response = $this->DI->reuse(
            isset($dep_map['ResponseFn']) ? $dep_map['ResponseFn'] : function (Corelib\DIContainer $I) {
                $class = (isset($I->dep_map['Response']) ? $I->dep_map['Response'] : '\Carcass\Application\Web_Response');
                return new $class($I->Request);
            }
        );

        $Injector->Router = $this->DI->reuse(
            isset($dep_map['RouterFn']) ? $dep_map['RouterFn'] : function (Corelib\DIContainer $I) {
                return Web_Router_Factory::assembleByConfig($I->ConfigReader->web->router);
            }
        );

        $Injector->FrontController = isset($dep_map['FrontControllerFn']) ? $dep_map['FrontControllerFn'] : function (Corelib\DIContainer $I) {
            $class = (isset($I->dep_map['FrontController']) ? $I->dep_map['FrontController'] : '\Carcass\Application\Web_FrontController');
            return new $class($I->Request, $I->Response, $I->Router, $I->ConfigReader->web);
        };
    }

    protected function setupApplicationDependencies() {
        $DependenciesConfig = $this->ConfigReader->get('dependencies');
        if (!$DependenciesConfig) {
            return;
        }

        $dependencies = Dependencies::getApplicationDependencies($lib_path, $DependenciesConfig);
        if (!$lib_path) {
            throw new \LogicException('dependencies.path is undefined in application configuration');
        }

        $lib_path = realpath($lib_path);

        $lib_pathes[$lib_path] = true;

        foreach ($dependencies as $dependency) {
            if (isset($dependency['source']['path'])) {
                $path = rtrim($dependency['source']['path'], '/');
                if (substr($path, 0, 1) != '/') {
                    $path = $lib_path . '/' . $path;
                }
                $lib_pathes[realpath($path)] = true;
            }
        }

        $this->Autoloader->addToIncludePath(array_keys($lib_pathes));
    }

    protected static function prefixNamespaces(array $list) {
        return array_map([get_called_class(), 'prefixNamespace'], $list);
    }

    protected static function prefixNamespace($name) {
        if (substr($name, 0, 1) === '_') {
            return (__NAMESPACE__ . '\\') . substr($name, 1);
        } elseif (substr($name, 0, 1) !== '\\') {
            return static::getFqClassName($name);
        }
        return $name;
    }

    protected function setupErrorHandler() {
        require_once __DIR__ . '/ErrorHandler.php';
        ErrorHandler::register();
    }

    protected function loadApplicationConfiguration() {
        if (!empty($this->options['env_data'])) {
            $env_data = (array)$this->options['env_data'];
        } else {
            $env_data = [];
            foreach ([$this->options['env_file'], $this->options['etc_file']] as $env_file) {
                $filename = $this->app_root . $env_file;
                if (file_exists($filename)) {
                    $env_data += (array)(include $filename);
                }
            }
        }
        $this->app_env = $env_data + static::$env_defaults;
        $this->app_env['app_root'] = & $this->app_root;
        if (!isset($this->app_env['configuration_name'])) {
            $this->app_env['configuration_name'] = null;
        }
        if (!isset($this->app_env['revision'])) {
            $this->app_env['revision'] = time();
        }
        if (!is_array($this->app_env['lib_path'])) {
            $this->app_env['lib_path'] = [$this->app_env['lib_path']];
        }
        if (empty($this->app_env['lib_path'])) {
            $this->app_env['lib_path'] = [$this->app_root . 'lib/'];
        }
        foreach (array_reverse(static::getCarcassLibDirs()) as $lib_dir) {
            array_unshift($this->app_env['lib_path'], $lib_dir);
        }
    }


    protected function setupRunMode() {
        if (empty($this->app_env['run_mode'])) {
            $this->app_env['run_mode'] = $this->getRunModeBySapi();
        }
    }

    protected function getRunModeBySapi() {
        return PHP_SAPI == 'cli' ? 'cli' : 'web';
    }

    protected function setupAutoloader() {
        require_once __DIR__ . '/Autoloader.php';
        $this->Autoloader = new Autoloader($this->app_env['lib_path']);
    }

    protected function setupConfigReader() {
        $this->ConfigReader = new Config\Reader($this->getConfigLocations());
        $this->ConfigReader->addConfigVar('APP_ROOT', $this->app_root);
    }

    protected function getConfigLocations() {
        $config_roots = [$this->getConfigAppPath()];
        if (!empty($this->app_env['cfg_path_extra'])) {
            $config_roots = array_merge($config_roots, static::fixPathes($this->app_env['cfg_path_extra']));
        }
        $result = [];
        foreach ($this->getConfigSubdirs() as $subdir) {
            foreach ($config_roots as $dir) {
                if (is_dir($dir . $subdir)) {
                    $result[] = $dir . $subdir;
                }
            }
        }
        return $result;
    }

    protected function getConfigAppPath() {
        return static::fixPath($this->app_root . $this->options['config_dir']);
    }

    protected function getConfigSubdirs() {
        return $this->app_env['configuration_name'] ? ['', $this->app_env['configuration_name'] . '/'] : [''];
    }

    protected function setupLogger() {
        $log_cfg = $this->ConfigReader->exportArrayFrom('application.log', []);
        if (!$log_cfg) {
            $log_cfg = static::getLogDefaults();
        }
        $this->Logger = new Log\Dispatcher($log_cfg);
    }

    protected static function getLogDefaults() {
        $result = [];
        if (ini_get('log_errors') && $target = ini_get('error_log')) {
            if ($target == 'syslog') {
                $result['syslog'] = ['Notice'];
            } else {
                $result['error_log'] = ['Notice'];
            }
        }
        if (ini_get('display_errors') && defined('STDERR')) {
            $result['file'] = ['Notice', ['filename' => STDERR]];
        }
        return $result;
    }


    protected function setupDebugger() {
        if ($this->ConfigReader->getPath('application.debugger.enable')) {
            $this->Debugger = new DevTools\Debugger($this->getDebuggerReporter());
        } else {
            $this->Debugger = new DevTools\DebuggerStub;
        }
    }

    /**
     * @return DevTools\BaseReporter
     */
    protected function getDebuggerReporter() {
        $config = $this->ConfigReader->getPath('application.debugger.reporter');
        if (is_scalar($config)) {
            $reporter_type = $config;
        } else {
            if ($config->has($this->app_env['run_mode'])) {
                $reporter_type = $config->get($this->app_env['run_mode']);
            }
        }
        if (!isset($reporter_type) && isset(static::$debug_reporter_defaults[$this->app_env['run_mode']])) {
            $reporter_type = static::$debug_reporter_defaults[$this->app_env['run_mode']];
        }
        return isset($reporter_type)
            ? DevTools\ReporterFactory::assembleByType($reporter_type)
            : DevTools\ReporterFactory::assembleDefault();
    }

    protected function setupPathManager() {
        $this->PathManager = new PathManager($this->app_root, $this->ConfigReader->exportArrayFrom('application.paths', []));
    }

    protected function setupConnectionManager() {
        $this->ConnectionManager = new Connection\Manager($this->ConfigReader->exportHashFrom('connections', []));
    }

    protected static function fixPathes(array $dirnames) {
        return array_map([get_called_class(), 'fixPath'], $dirnames);
    }

    protected static function fixPath($dirname) {
        if (substr($dirname, 0, 1) != '/') {
            $dirname = realpath($dirname);
            if (!$dirname) {
                throw new \RuntimeException("Cannot resolve realpath of '$dirname'");
            }
        }
        return rtrim($dirname, '/') . '/';
    }

    protected static function getCarcassLibDirs() {
        return [dirname(dirname(__DIR__)) . '/'];
    }

    protected function __clone() {
        // pass
    }

}
