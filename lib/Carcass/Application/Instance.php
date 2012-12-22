<?php

namespace Carcass\Application;

use Carcass\Corelib as Corelib;
use Carcass\Config as Config;
use Carcass\Log as Log;
use Carcass\DevTools as DevTools;
use Carcass\Connection as Connection;

class Instance {

    protected static $env_defaults = [
        'configuration_name' => null,
        'lib_path' => [],
        'run_mode' => null,
        'namespace' => null,
    ];

    protected static $opt_defaults = [
        'env_file'   => 'env.php',
        'config_dir' => 'config/',
    ];

    protected static $debug_reporter_defaults = [
        'cli' => 'console',
        'web' => 'firebug',
    ];

    protected $app_root;
    protected $app_env;

    protected $options;

    protected $Autoloader;
    protected $ConfigReader;
    protected $Logger;
    protected $Debugger;
    protected $PathManager;
    protected $Injector;
    protected $ConnectionManager;
    protected $Crypter = null;

    private static $instance = null;

    public static function run($app_root, array $overrides = []) {
        new static($app_root, $overrides);
    }

    public static function getEnv($key, $default_value = null) {
        return array_key_exists($key, static::$instance->app_env) ? static::$instance->app_env[$key] : null;
    }

    public static function getFqClassName($class_name) {
        $app_namespace = static::getEnv('namespace');
        return $app_namespace ? "$app_namespace\\$class_name" : "\\$class_name";
    }

    protected function __construct($app_root, array $overrides = []) {
        if (static::$instance) {
            throw new \LogicException('Application already created');
        }
        static::$instance = $this;
        $this->app_root = static::fixPath($app_root);
        $this->options = $overrides + static::$opt_defaults;
        $this->bootstrap();
        $this->executeRunMode();
    }

    protected function bootstrap() {
        $this->setupErrorHandler();
        $this->loadApplicationConfiguration();
        $this->setupRunMode();
        $this->setupAutoloader();
        $this->setupConfigReader();
        $this->setupLogger();
        $this->setupDebugger();
        $this->setupPathManager();
        $this->setupDependencies();
    }


    protected function executeRunMode() {
        $this->Injector->FrontController->run();
    }

    protected function setupDependencies() {
        $this->Injector = Injector::setInstance(new Corelib\Injector);

        $dep_config = $this->ConfigReader->exportArrayFrom('application.dependencies.' . $this->app_env['run_mode'], []);
        $dep_map = $this->prefixNamespaces(isset($dep_config['map']) && is_array($dep_config['map']) ? $dep_config['map'] : []);

        if (isset($dep_config['fn']) && $dep_config['fn'] instanceof Closure) {
            $setupFn = $dep_config['fn'];
        } else {
            $setupFn = [ $this, 'setupDependencies' . $this->app_env['run_mode'] ];
        }

        $this->Injector->dep_map = $dep_map;
        $this->Injector->app_env = $this->app_env;
        $this->Injector->ConfigReader = $this->ConfigReader;
        $this->Injector->PathManager  = $this->PathManager;
        $this->Injector->Debugger     = $this->Debugger;
        $this->Injector->Logger       = $this->Logger;

        if (is_callable($setupFn)) {
            $setupFn($this->Injector, $dep_map);
        } else {
            throw new \LogicException("Cannot setupDependencies() for {$this->app_env['run_mode']} mode: no setup function "
                                    . "is defined in configuration, and mode is not supported internally");
        }
    }

    protected function setupDependenciesCli($Injector, array $dep_map) {
        $this->Injector->Request = $this->Injector->reuse(isset($dep_map['RequestFn']) ? $dep_map['RequestFn'] : function($I) {
            $class = (isset($I->dep_map['RequestBuilder']) ? $I->dep_map['RequestBuilder'] : '\Carcass\Application\Cli_RequestBuilder');
            class_exists($class);
            return $class::assembleRequest();
        });
        $this->Injector->Response = $this->Injector->reuse(isset($dep_map['ResponseFn']) ? $dep_map['ResponseFn'] : function($I) {
            $class = (isset($I->dep_map['Response']) ? $I->dep_map['Response'] : '\Carcass\Application\Cli_Response');
            return new $class;
        });
        $this->Injector->Router = $this->Injector->reuse(isset($dep_map['RouterFn']) ? $dep_map['RouterFn'] : function($I) {
            $class = (isset($I->dep_map['Router']) ? $I->dep_map['Router'] : '\Carcass\Application\Cli_Router');
            return new $class;
        });
        $this->Injector->FrontController = isset($dep_map['FrontControllerFn']) ? $dep_map['FrontControllerFn'] : function($I) {
            $class = (isset($I->dep_map['FrontController']) ? $I->dep_map['FrontController'] : '\Carcass\Application\Cli_FrontController');
            return new $class($I->Request, $I->Response, $I->Router);
        };
    }


    protected static function prefixNamespaces(array $list) {
        return array_map([get_called_class(), 'prefixNamespace'], $list);
    }

    protected static function prefixNamespace($name) {
        if (substr($name, 0, 1) === '_') {
            return __NAMESPACE__ . '\\' . substr($name, 1);
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
        $this->app_env = (array)(include $this->app_root . $this->options['env_file']) + static::$env_defaults;
        $this->app_env['app_root'] = &$this->app_root;
        if (!isset($this->app_env['configuration_name'])) {
            $this->app_env['configuration_name'] = null;
        }
        if (!is_array($this->app_env['lib_path'])) {
            $this->app_env['lib_path'] = [$this->app_env['lib_path']];
        }
        if (empty($this->app_env['lib_path'])) {
            $this->app_env['lib_path'] = [$this->app_root . 'lib/'];
        }
        array_unshift($this->app_env['lib_path'], static::getCarcassRootDir());
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
        return $this->app_env['configuration_name'] ? [$this->app_env['configuration_name'] . '/', ''] : [''];
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
        if ($this->ConfigReader->getPath('application.debug.enable')) {
            $this->Debugger = new DevTools\Debugger($this->getDebuggerReporter());
        } else {
            $this->Debugger = new DevTools\DebuggerStub;
        }
    }

    protected function getDebuggerReporter() {
        $config = $this->ConfigReader->getPath('application.debug.reporter');
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
        return array_map([self, 'fixPath'], $dirnames);
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

    protected function getApplicationCrypter() {
        if (null === $this->Crypter) {
            $crypter_settings = $this->ConfigReader->getPath('application.secret', null);
            if (is_object($crypter_settings)) {
                $crypter_settings = $crypter_settings->exportArray();
            }
            $this->Crypter = new Corelib\Crypter($crypter_settings);
        }
        return $this->Crypter;
    }

    protected static function getCarcassRootDir() {
        return dirname(dirname(__DIR__));
    }

    protected function __clone() {
        // pass
    }

}
