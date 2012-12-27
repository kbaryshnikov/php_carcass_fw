<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_Router_Map implements Web_RouterInterface {

    protected static $default_options = [
        'auto_trailing_slashes' => true,
    ];

    protected $routes = [];
    protected $rev_routes = [];

    protected $options = [];

    public function __construct(array $map, array $options = []) {
        $this->options = $options + static::$default_options;
        $this->loadMap($map);
    }

    public function route(Corelib\Request $Request, ControllerInterface $Controller) {
        $uri = $Request->Env->has('DOCUMENT_URI') ? $Request->Env->DOCUMENT_URI : strtok($Request->Env->REQUEST_URI, '?');
        $route = $this->findRoute($uri);
        if (!$route) {
            $Controller->dispatchNotFound("Route not found for $uri");
        } else {
            $Controller->dispatch($route[0], new Corelib\Hash($route[1]));
        }
    }

    public function getUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->getUrlInstanceByRoute($route, $args)->getRelative();
    }

    public function getAbsoluteUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->getUrlInstanceByRoute($route, $args)->getAbsolute($Request->Env->HOST, $Request->Env->get('SCHEME', 'http'));
    }

    protected function getUrlInstanceByRoute($route, array $args) {
        $url = $this->findUrl($route);
        if (null === $url) {
            throw new \InvalidArgumentException("Route not registered: '$route'");
        }
        return new Corelib\Url($url, $args);
    }

    protected function findRoute($uri) {
        $uri_len = strlen($uri);
        foreach ($this->routes as $prefix => $variants) {
            if ($prefix == $uri && isset($variants[''])) {
                return [$variants[''], []];
            }
            $prefix_len = strlen($prefix);
            if ($prefix_len > $uri_len) {
                continue;
            }
            if (0 != strncmp($uri, $prefix, $prefix_len)) {
                continue;
            }
            $suffix = substr($uri, $prefix_len);
            foreach ($variants as $regexp => $route) {
                if ($regexp === '') {
                    continue;
                }
                if (preg_match($regexp, $suffix, $matches)) {
                    $args = Corelib\ArrayTools::filterAssoc($matches, function($key) {
                        return is_string($key);
                    });
                    return [$route, $args];
                }
            }
        }
        return null;
    }

    protected function loadMap(array $map) {
        $this->rev_routes = Corelib\ArrayTools::mapAssoc($map, function(&$key, $value) {
            $key = static::normalize($key);
            return $value;
        });
        $this->routes = $this->compileMap($this->rev_routes);
    }

    protected function findUrl($route) {
        $route = static::normalize($route);
        if (!isset($this->rev_routes[$route])) {
            return null;
        }
        return $this->rev_routes[$route];
    }

    protected static function normalize($route) {
        return ucfirst(strtok($route, '.')) . '.' . ucfirst(strtok(null) ?: 'Default');
    }

    protected function compileMap(array $map) {
        $routes = [];
        foreach ($map as $route => $url_template) {
            if (substr($url_template, 0, 1) != '/') {
                throw new \RuntimeException('url template must start with a slash in [ ' . $url_template . ' => ' . $route . ' ]');
            }

            try {
                list ($prefix, $suffix_regexp) = Corelib\UrlTemplate::compile($url_template);
            } catch (\Exception $e) {
                throw new \RuntimeException($e->getMessage() . ' in [ ' . $url_template . ' => ' . $route . ' ]');
            }

            if (isset($routes[$prefix][$suffix_regexp])) {
                throw new \RuntimeException("Duplicate routes: '{$routes[$prefix][$suffix_regexp]}' and '{$route}'");
            }
            $routes[$prefix][$suffix_regexp] = $route;
        }
        uksort($routes, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        return $routes;
    }
}
