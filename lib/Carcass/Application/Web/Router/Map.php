<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;

/**
 * Map router. Routes by the config-defined route map.
 *
 * @package Carcass\Application
 */
class Web_Router_Map implements Web_Router_Interface {
    use Web_Router_StaticTrait;

    protected static $default_options = [
        'auto_trailing_slashes' => true,
    ];

    protected $routes = [];
    protected $rev_routes = [];
    protected $options = [];

    /**
     * @param array $map
     * @param array $options
     */
    public function __construct(array $map, array $options = []) {
        $this->options = $options + static::$default_options;
        $this->loadMap($map);
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param ControllerInterface $Controller
     * @return mixed
     */
    public function route(Corelib\Request $Request, ControllerInterface $Controller) {
        $uri = $Request->Env->has('DOCUMENT_URI') ? $Request->Env->DOCUMENT_URI : strtok($Request->Env->REQUEST_URI, '?');
        $route = $this->findRoute($uri);
        return $this->dispatchRoute($route, $Controller);
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param Web_PageController $Controller
     * @param string|null $action
     * @param array $args
     * @return string
     */
    public function getPageUrl(Corelib\Request $Request, Web_PageController $Controller, $action = null, array $args = []) {
        return $this->getUrl($Request, $this->getRouteByController($Controller, $action), $args);
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param Web_PageController $Controller
     * @param string|null $action
     * @param array $args
     * @return string
     */
    public function getPageAbsoluteUrl(Corelib\Request $Request, Web_PageController $Controller, $action = null, array $args = []) {
        return $this->getAbsoluteUrl($Request, $this->getRouteByController($Controller, $action), $args);
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param string $route
     * @param array $args
     * @return string
     */
    public function getUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->getUrlInstanceByRoute($route, $args)->getRelative();
    }

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param $route
     * @param array $args
     * @return string
     */
    public function getAbsoluteUrl(Corelib\Request $Request, $route, array $args = []) {
        return $this->getUrlInstanceByRoute($route, $args)->getAbsolute($Request->Env->HOST, $Request->Env->get('SCHEME', 'http'));
    }

    /**
     * @param $route
     * @param array $args
     * @return \Carcass\Corelib\Url
     * @throws \InvalidArgumentException
     */
    protected function getUrlInstanceByRoute($route, array $args) {
        $url = $this->findUrl($route);
        if (null === $url) {
            throw new \InvalidArgumentException("Route not registered: '$route'");
        }
        return $this->createUrlInstance($url, $args);
    }

    /**
     * @param string $url
     * @param array $args
     * @return Corelib\Url
     */
    protected function createUrlInstance($url, array $args) {
        return new Corelib\Url($url, $args);
    }

    /**
     * @param $uri
     * @return array|null
     */
    protected function findRoute($uri) {
        $uri_len = strlen($uri);
        foreach ($this->routes ? : [] as $prefix => $variants) {
            $prefix_len = strlen($prefix);
            if ($prefix_len > $uri_len) {
                continue;
            }
            if ($prefix_len == $uri_len) {
                if ($prefix === $uri && isset($variants[''])) {
                    return [$variants[''], []];
                }
            }
            if (0 != strncmp($uri, $prefix, $prefix_len)) {
                continue;
            }
            $suffix = substr($uri, $prefix_len);
            foreach ($variants ? : [] as $regexp => $route) {
                if ($regexp === '') {
                    continue;
                }
                if (preg_match($regexp, $suffix, $matches)) {
                    $args = Corelib\ArrayTools::filterAssoc(
                        $matches, function ($key) {
                            return is_string($key);
                        }
                    );
                    return [$route, $args];
                }
            }
        }
        return null;
    }

    /**
     * @param array $map
     */
    protected function loadMap(array $map) {
        $this->rev_routes = Corelib\ArrayTools::mapAssoc(
            $map, function (&$key, $value) {
                $key = static::normalize($key);
                return $value;
            }
        );
        $this->routes = $this->compileMap($this->rev_routes);
    }

    /**
     * @param $route
     * @return string|null
     */
    protected function findUrl($route) {
        $route = static::normalize($route);
        if (!isset($this->rev_routes[$route])) {
            return null;
        }
        return $this->rev_routes[$route];
    }

    /**
     * @param $route
     * @return string
     */
    protected static function normalize($route) {
        return ucfirst(strtok($route, '.')) . '.' . ucfirst(strtok(null) ? : 'Default');
    }

    /**
     * @param array $map
     * @return array
     * @throws \RuntimeException
     */
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
        uksort(
            $routes, function ($a, $b) {
                return strlen($b) - strlen($a);
            }
        );
        return $routes;
    }

    protected function getRouteByController(Web_PageController $Controller, $action = null) {
        $tokens = explode('\\', get_class($Controller));
        $route_name = end($tokens);
        if ($action) {
            $route_name .= ".{$action}";
        }
        return $route_name;
    }

    protected function dispatchRoute($route, ControllerInterface $Controller) {
        if (!$route) {
            return $Controller->dispatchNotFound("Route not found");
        } else {
            return $Controller->dispatch($route[0], new Corelib\Hash($route[1]));
        }
    }

}
