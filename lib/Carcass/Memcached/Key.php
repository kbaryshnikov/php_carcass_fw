<?php

namespace Carcass\Memcached;

use Carcass\Corelib;

class Key {

    protected $Builder;

    protected $opts = [
        'prefix' => '',
        'suffix' => '',
    ];

    private function __construct($Builder) {
        $this->Builder = $Builder;
    }

    public function getClone(array $opts = null) {
        $that = clone $this;
        if (null !== $opts) {
            $that->setOptions($opts);
        }
        return $that;
    }

    public function parse($args, array $opts = []) {
        $this->Builder->cleanAll();
        $opts += $this->opts;
        return $opts['prefix'] . $this->Builder->parse($args) . $opts['suffix'];
    }

    public function setOptions(array $opts) {
        $this->setPrefix(isset($opts['prefix']) ? $opts['prefix'] : '');
        $this->setSuffix(isset($opts['suffix']) ? $opts['suffix'] : '');
        return $this;
    }

    public function setPrefix($prefix) {
        $this->opts['prefix'] = $prefix;
        return $this;
    }

    public function setSuffix($suffix) {
        $this->opts['suffix'] = $suffix;
        return $this;
    }

    public static function createMulti(array $templates, array $opts = []) {
        $result = [];
        foreach ($templates as $key => $template) {
            if ($template instanceof \Closure) {
                $result[$key] = $template('getClone', $opts);
            } elseif (is_array($template)) {
                $result[$key] = static::createMulti($template, $opts);
            } else {
                $result[$template] = static::create($template, $opts);
            }
        }
        return $result;
    }

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
