<?php

namespace Carcass\Application;

use Carcass\Config as Config;

class Web_Router_Config {

    protected static $default_options = [
        'auto_trailing_slashes' => true,
    ];

    protected $routes = [];

    protected $options = [];

    public function __construct(Config\Item $Config = null) {
        $this->loadFromConfig($Config ?: static::getAppRoutesConfig());
    }

    protected function loadFromConfig(Config\Item $Config) {
        $this->options = $Config->exportArrayFrom('options', []) + static::$default_options;
        $this->routes = [];
        foreach ($Config->routes as $url_template => $route) {
            if (substr($url_template, 0, 1) != '/') {
                $url_template = '/' . $url_template;
            }
            if (!preg_match('~^(/[^{])*(.*)?$~', $url_template, $matches)) {
                throw new \LogicException("url template parser failed, this should never happen");
            }
            $prefix = $matches[1];
            $suffix_pattern = empty($matches[2]) ? null : $matches[2];
            if ($suffix_pattern) {
                $suffix = static::compilePattern($suffix_pattern);
            }
        }
    }

    protected static function compilePattern($pattern) {
    }

    protected static function getAppRoutesConfig() {
        if (!$RoutesConfig = Application\Instance::getConfig()->getPath('web.routing')) {
            throw new \RuntimeException('Could not find web.routes section in configuration');
        }
        return $RoutesConfig;
    }

}
