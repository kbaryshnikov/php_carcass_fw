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

    protected static $log_writer_defaults = [];

    protected static $run_mode_defaults = [
        'cli' => [
            'request_builder_class' => 'Cli_RequestBuilder',
            'response_class' => 'Cli_Response',
            'router_class' => 'Cli_Router',
            'fc_class' => 'Cli_FrontController',
        ],
        'web' => [
            'request_builder_class' => 'Web_RequestBuilder',
            'response_class' => 'Web_Response',
            'router_class' => 'Web_Router_Config',
            'fc_class' => 'Web_FrontController',
        ],
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
    protected $ConnectionManager;

    private static $instance = null;

    public static function run($app_root, array $overrides = []) {
        new static($app_root, $overrides);
    }

    public static function getConfigReader() {
        return static::$instance->ConfigReader;
    }

    public static function getAutoloader() {
        return static::$instance->Autoloader;
    }

    public static function getLogger() {
        return static::$instance->Logger;
    }

    public static function getDebugger() {
        return static::$instance->Debugger;
    }

    public static function getConnectionManager() {
        return static::$instance->ConnectionManager;
    }

    public static function getPathManager() {
        return static::$instance->PathManager;
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
        $this->setupConnectionManager();
    }


    protected function executeRunMode() {
        $dependency_classes = $this->getRunModeDependencyClasses();
        $Request = $dependency_classes['request_builder_class']::assembleRequest();
        foreach (['_GET', '_POST', '_SERVER', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV'] as $global) {
            unset($$global);
        }
        $FrontController = new $dependency_classes['fc_class']($Request, [
            'router_class' => $dependency_classes['router_class'],
            'response_class' => $dependency_classes['response_class'],
        ]);
        $FrontController->run();
    }

    protected function getRunModeDependencyClasses() {
        $config = $this->ConfigReader->exportArrayFrom('application.run_mode.' . $this->app_env['run_mode'], []);
        if (isset(static::$run_mode_defaults[$this->app_env['run_mode']])) {
            $config = array_filter($config) + static::$run_mode_defaults[$this->app_env['run_mode']];
        }
        return array_map(function($item) {
            return substr($item, 0, 1) === '\\' ? $item : (__NAMESPACE__ . '\\' . $item);
        }, $config);
    }


    protected function setupErrorHandler() {
        require_once __DIR__ . '/ErrorHandler.php';
        ErrorHandler::register();
    }


    protected function loadApplicationConfiguration() {
        $this->app_env = (array)(include $this->app_root . $this->options['env_file']) + static::$env_defaults;
        $this->app_env['app_root'] = &$this->app_root;
        if (!isset($this->app_env['configuration_name'])) {
            throw new \RuntimeException('configuration_name is not defined in env');
        }
        if (!is_array($this->app_env['lib_path'])) {
            $this->app_env['lib_path'] = [];
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
        return [$this->app_env['configuration_name'] . '/', ''];
    }


    protected function setupLogger() {
        $this->Logger = new Log\Dispatcher(self::$log_writer_defaults + $this->ConfigReader->exportArrayFrom('application.log', []));
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

    protected static function getCarcassRootDir() {
        return dirname(dirname(__DIR__));
    }

    protected function __clone() {
        // pass
    }

}
