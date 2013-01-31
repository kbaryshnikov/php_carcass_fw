<?php

namespace Carcass\Less;

use Carcass\Corelib;

class TwigExtension extends \Twig_Extension {

    protected $Dispatcher;
    protected $url_prefix;

    public function __construct($src_path, $target_path, $url_prefix, array $cacher_config) {
        $this->url_prefix = rtrim($url_prefix, '/');
        $this->Dispatcher = new Dispatcher($this->assembleCacher($cacher_config), $src_path, $target_path);
    }

    protected function assembleCacher(array $cacher_config) {
        return Corelib\ObjectTools::construct(
            Corelib\ObjectTools::resolveRelativeClassName($cacher_config['class'], __NAMESPACE__ . '\Cacher_'),
            $cacher_config['args']
        );
    }

    public function getName() {
        return 'CarcassLess';
    }

    public function getFunctions() {
        return ['less' => new Twig_Function_Method($this, 'less')];
    }

    public function less($file) {
        try {
            return $this->url_prefix . $this->Dispatcher->compile($file, $mtime) . '?' . $mtime;
        } catch (\Exception $e) {
            throw new Twig_Error($e->getMessage(), 0, $e);
        }
    }

}
