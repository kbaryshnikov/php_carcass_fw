<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Less;

use Carcass\Corelib;

/**
 * LESS Twig Extension
 * @package Carcass\Less
 */
class TwigExtension extends \Twig_Extension {

    /**
     * @var Dispatcher
     */
    protected $Dispatcher;
    /**
     * @var string
     */
    protected $url_prefix;

    /**
     * @param string $src_path
     * @param string $target_path
     * @param string $url_prefix
     * @param array $cacher_config
     */
    public function __construct($src_path, $target_path, $url_prefix, array $cacher_config) {
        $this->url_prefix = rtrim($url_prefix, '/');
        $this->Dispatcher = new Dispatcher($this->assembleCacher($cacher_config), $src_path, $target_path);
    }

    /**
     * @param array $cacher_config
     * @return Cacher_Interface
     */
    protected function assembleCacher(array $cacher_config) {
        return Corelib\ObjectTools::construct(
            Corelib\ObjectTools::resolveRelativeClassName($cacher_config['class'], __NAMESPACE__ . '\Cacher_'),
            $cacher_config['args']
        );
    }

    /**
     * @return string
     */
    public function getName() {
        return 'CarcassLess';
    }

    /**
     * @return array
     */
    public function getFunctions() {
        return ['less' => new \Twig_Function_Method($this, 'less')];
    }

    /**
     * @param $file
     * @return string
     * @throws \Twig_Error
     */
    public function less($file) {
        try {
            return $this->url_prefix . $this->Dispatcher->compile($file, $mtime) . '?' . $mtime;
        } catch (\Exception $e) {
            throw new \Twig_Error($e->getMessage(), 0, $e);
        }
    }

}
