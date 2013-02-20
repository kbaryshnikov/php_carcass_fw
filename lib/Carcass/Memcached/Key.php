<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Memcached;

use Carcass\Corelib;

/**
 * Memcached key factory
 * @package Carcass\Memcached
 */
class Key {

    /**
     * @var \Carcass\Corelib\StringTemplate
     */
    protected $Builder;

    /**
     * @var array
     */
    protected $opts = [
        'prefix' => '',
        'suffix' => '',
    ];

    /**
     * @param \Carcass\Corelib\StringTemplate $Builder
     */
    private function __construct(Corelib\StringTemplate $Builder) {
        $this->Builder = $Builder;
    }

    /**
     * @param $args
     * @param array $opts
     * @return string
     */
    public function parse($args, array $opts = []) {
        $this->Builder->cleanAll();
        $opts += $this->opts;
        return $opts['prefix'] . $this->Builder->parse($args) . $opts['suffix'];
    }

    /**
     * @param array $opts
     * @return $this
     */
    public function setOptions(array $opts) {
        $this->setPrefix(isset($opts['prefix']) ? $opts['prefix'] : '');
        $this->setSuffix(isset($opts['suffix']) ? $opts['suffix'] : '');
        return $this;
    }

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix) {
        $this->opts['prefix'] = $prefix;
        return $this;
    }

    /**
     * @param string $suffix
     * @return $this
     */
    public function setSuffix($suffix) {
        $this->opts['suffix'] = $suffix;
        return $this;
    }

    /**
     * @param array $templates
     * @param array $opts
     * @return array
     */
    public static function createMulti(array $templates, array $opts = []) {
        $result = [];
        foreach ($templates as $key => $template) {
            if ($template instanceof \Closure) {
                $result[$key] = clone $template;
                $result[$key]('setOptions', $opts);
            } elseif (is_array($template)) {
                $result[$key] = static::createMulti($template, $opts);
            } else {
                $result[$template] = static::create($template, $opts);
            }
        }
        return $result;
    }

    /**
     * @param $template
     * @param array $opts
     * @return callable
     * @throws \InvalidArgumentException
     */
    public static function create($template, array $opts = []) {
        $Key = new self(new KeyBuilder($template, $opts));
        return function() use ($Key) {
            $args = func_get_args();
            if (empty($args)) {
                $args = [[]];
            }
            if (Corelib\ArrayTools::isTraversable($args[0])) {
                return $Key->parse($args[0], isset($args[1]) ? $args[1] : []);
            }
            if (is_string($args[0])) {
                if (!method_exists($Key, $args[0])) {
                    throw new \InvalidArgumentException("Invalid call: '{$args[0]}'");
                }
                return call_user_func_array([$Key, array_shift($args)], $args);
            }
            throw new \InvalidArgumentException("Invalid call");
        };
    }

}
