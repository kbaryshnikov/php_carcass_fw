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
 * Twig renderer. Requires the Twig template engine as a dependency.
 * @package Carcass\Application
 */
class Web_Renderer_Twig extends Web_Renderer_Base {

    /**
     * @var string|null
     */
    protected $template_file = null;

    protected $settings = [];

    /**
     * @param array $settings
     * @param string|null $template_file
     */
    public function __construct(array $settings = null, $template_file = null) {
        $settings and $this->configure($settings);
        $template_file and $this->setTemplateFile($template_file);
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function configure(array $settings) {
        $this->settings = $settings + $this->settings;
        return $this;
    }

    /**
     * @param $template_file
     * @return $this
     */
    public function setTemplateFile($template_file) {
        $this->template_file = (string)$template_file;
        return $this;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status) {
        parent::setStatus($status);
        if ($status >= 400) {
            $template_file = null;
            if (isset($this->settings['error_page'])) {
                if (is_array($this->settings['error_page']) && isset($this->settings['error_page'])) {
                    $template_file = $this->settings['error_page'][$status];
                }
                if (is_string($this->settings['error_page'])) {
                    $template_file = sprintf($this->settings['error_page'], $status);
                }
            }
            $this->template_file = $template_file;
        }
        return $this;
    }

    /**
     * @param $path
     * @param $default
     * @return array|null
     * @throws \LogicException
     */
    protected function getSetting($path, $default = NAN) {
        $ptr = $this->settings;
        foreach (explode('.', $path) as $token) {
            if (is_array($ptr) && isset($ptr[$token])) {
                $ptr = $ptr[$token];
            } else {
                if (NAN !== $default) {
                    return $default;
                }
                throw new \LogicException("Required setting missing: '$path'");
            }
        }
        return $ptr;
    }

    protected function doRender(array $render_data) {
        return $this->template_file === null ? '' : $this->assembleTwigEnv()->loadTemplate($this->template_file)->render($render_data);
    }

    /**
     * @return \Twig_Environment
     */
    protected function assembleTwigEnv() {
        self::ensureTwigLibraryIsLoaded();
        $env_args = (array)$this->getSetting('env', []);
        if (!isset($env_args['autoescape'])) {
            $env_args['autoescape'] = true;
        }
        $Env = new \Twig_Environment($this->assembleTwigLoader(), $env_args);
        $Env->addFunction(new \Twig_SimpleFunction('url', [$this, 'getTwigUrl']));
        $Env->addFunction(new \Twig_SimpleFunction('static', [$this, 'getTwigStaticUrl']));
        foreach ($this->getSetting('extensions', []) as $ext_class => $ext_ctor_args) {
            /** @var \Twig_Extension $Extension */
            $Extension = Corelib\ObjectTools::construct(
                Corelib\ObjectTools::resolveRelativeClassName($ext_class, '\Twig_Extension_'),
                $ext_ctor_args ?: []
            );
            $Env->addExtension($Extension);
        }
        return $Env;
    }

    /**
     * Callback for url builder extension
     * @param string $route_name Prefix with ':' for absolute URL
     * @param array $args
     * @return string
     */
    public function getTwigUrl($route_name, array $args = []) {
        if ($this->getTwigIsAbsolute($route_name)) {
            return Injector::getRouter()->getAbsoluteUrl(Injector::getRequest(), $route_name, $args);
        } else {
            return Injector::getRouter()->getUrl(Injector::getRequest(), $route_name, $args);
        }
    }

    /**
     * Callback for url builder extension
     * @param string $url Prefix with ':' for absolute URL
     * @return mixed
     */
    public function getTwigStaticUrl($url) {
        $absolute = $this->getTwigIsAbsolute($url);
        return Injector::getRouter()->getStaticUrl(Injector::getRequest(), $url, $absolute);
    }

    /**
     * @param $url NB: gets $url by references, truncates the ':' prefix of absolute urls
     * @return bool
     */
    protected function getTwigIsAbsolute(&$url) {
        if (substr($url, 0, 1) == ':') {
            $absolute = true;
            $url = substr($url, 1);
        } else {
            $absolute = false;
        }
        return $absolute;
    }

    protected function assembleTwigLoader() {
        return Corelib\ObjectTools::construct(
            Corelib\ObjectTools::resolveRelativeClassName($this->getSetting('loader.class', '_Filesystem'), '\Twig_Loader_'),
            (array)$this->getSetting('loader.args', [])
        );
    }

    protected static function ensureTwigLibraryIsLoaded() {
        if (!class_exists('\Twig_Autoloader', false)) {
            require_once 'Twig/Autoloader.php';
            \Twig_Autoloader::register();
        }
    }

}
