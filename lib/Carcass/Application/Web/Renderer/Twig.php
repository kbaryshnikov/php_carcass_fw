<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_Renderer_Twig extends Web_Renderer_Base {

    protected
        $template_file = null,
        $settings = [];

    public function __construct(array $settings = null, $template_file = null) {
        $settings and $this->configure($settings);
        $template_file and $this->setTemplateFile($template_file);
    }

    public function configure(array $settings) {
        $this->settings = $settings + $this->settings;
        return $this;
    }

    public function setTemplateFile($template_file) {
        $this->template_file = (string)$template_file;
        return $this;
    }

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
        return $this->assembleTwigEnv()->loadTemplate($this->template_file)->render($render_data);
    }

    protected function assembleTwigEnv() {
        self::ensureTwigLibraryIsLoaded();
        $env_args = (array)$this->getSetting('env', []);
        if (!isset($env_args['autoescape'])) {
            $env_args['autoescape'] = true;
        }
        $Env = new \Twig_Environment($this->assembleTwigLoader(), $env_args);
        foreach ($this->getSetting('extensions', []) as $ext_class => $ext_ctor_args) {
            $Env->addExtension(
                Corelib\ObjectTools::construct(
                    Corelib\ObjectTools::resolveRelativeClassName($ext_class, '\Twig_Extension_'),
                    $ext_ctor_args ?: []
                )
            );
        }
    }

    protected function assembleTwigLoader() {
        return Corelib\ObjectTools::construct(
            Corelib\ObjectTools::resolveRelativeClassName($this->getSetting('loader.type', 'Filesystem'), '\Twig_Loader_'),
            (array)$this->getSetting('loader.args', [])
        );
    }

    protected static function ensureTwigLibraryIsLoaded() {
        if (!class_exists('Twig_Autoloader', false)) {
            require_once 'Twig/Autoloader.php';
            Twig_Autoloader::register();
        }
    }

}

