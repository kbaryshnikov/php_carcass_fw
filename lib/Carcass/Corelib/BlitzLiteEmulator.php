<?php

namespace Carcass\Corelib;

class BlitzLiteEmulator {

    protected
        $set = array(),
        $ctx = array(),
        $stack = array(),
        $compiled = null,
        $__count = null,
        $__pos = null,
        $result = null,
        $tpl = null;

    public static function constructFromFile($file) {
        return new static($file);
    }

    public static function constructFromString($string) {
        $self = new static;
        $self->load($string);
        return $self;
    }

    public static function parseString($string, array $args = []) {
        return static::constructFromString($string)->parse($args);
    }

    public function __construct($file = null) {
        if ($file !== null) {
            $this->loadFile($file);
        }
    }

    public function load($string) {
        $this->tpl = $string;
        $this->compiled = null;
        $this->clear();
    }

    public function loadFile($string) {
        $this->tpl = file_get_contents($string);
        $this->compiled = null;
        $this->clear();
    }

    public function clear() {
        $this->set = array();
        $this->result = null;
    }

    public function set(array $set) {
        $this->set = $set + $this->set;
        $this->result = null;
    }

    public function parse(array $set = null) {
        if ($set !== null) {
            $this->set($set);
        }
        if ($this->compiled === null) {
            $this->__compile();
        }
        $this->result = $this->__execute();
        return $this->result;
    }

    public function display(array $set = null) {
        if ($set !== null) {
            $this->set($set);
        }
        if ($this->result === null) {
            $this->parse();
        }
        print $this->result;
    }

    protected function __getValue($addr) {
        if (is_numeric($addr)) {
            return $addr;
        }
        $ptr = $this->context;
        foreach (explode('.', trim($addr)) as $k) {
            if (isset($ptr[$k])) {
                $ptr = $ptr[$k];
            } else {
                $ptr = false;
                break;
            }
        }
        return $ptr;
    }

    protected function __push() {
        array_push($this->stack, $this->context);
        array_push($this->stack, $this->__pos);
        array_push($this->stack, $this->__count);
    }

    protected function __pop() {
        $this->__count  = array_pop($this->stack);
        $this->__pos    = array_pop($this->stack);
        $this->context  = array_pop($this->stack);
    }

    protected function __ctxBegin($addr) {
        $this->__push();
        $ptr = $this->__getValue($addr);
        if (false !== $ptr && is_array($ptr) && count($ptr)) {
            $result = is_numeric(key($ptr)) ? $ptr : array($ptr);
        } else {
            $result = array();
        }
        $this->__pos = 0;
        $this->__count = count($result);
        return $result;
    }

    protected function __print($addr) {
        $ptr = $this->__getValue($addr);
        if (is_scalar($ptr) && false !== $ptr) {
            print $ptr;
        }
    }

    protected function __special($addr) {
        if (substr($addr, 0, 1) != '_') return null;
        $addr = strtolower($addr);
        switch ($addr) {
            case '_first':
                return ($this->__pos == 1);
            case '_last':
                return ($this->__pos == $this->__count);
            case '_odd':
                return ($this->__pos % 2 != 0);
            case '_even':
                return ($this->__pos % 2 == 0);
            case '_num':
                return $this->__pos;
            case '_total':
                return $this->__count;
            default:
                return null;
        }
    }

    protected function __if($addr) {
        $special = $this->__special($addr);
        if ($special !== null) {
            $result = $special;
        } else {
            $ptr = $this->__getValue($addr);
            $result = !empty($ptr);
        }
        $this->__push();
        return $result;
    }

    protected function __unless($addr) {
        return !$this->__if($addr);
    }

    protected function __ctxEnd() {
        $this->__pop();
    }

    protected function __execute() {
        $this->context = $this->set;
        $this->stack = array();
        ob_start();
        eval($this->compiled);
        return ob_get_clean();
    }

    protected function __dispatchCall($method, array $args) {
        if (method_exists($this, $method)) {
            print call_user_func_array(array($this, $method), $args);
        } else {
            throw new \Exception("Undefined method: '$method'");
        }
    }

    protected function __compile() {
        static $compile_regexps = array(
            '/{{\s*BEGIN\s+([\w\d_.]+)\s*}}/i'
                => '<?php $___loop = $this->__ctxBegin("$1"); foreach ($___loop as $___context) { ++$this->__pos; $this->context = $___context; ?>',
            '/{{\s*END(?:\s+(?:[\w\d._]+))?\s*}}/i'
                => '<?php } $this->__ctxEnd(); ?>',
            '/{{\s*IF\s+([\w\d_.]+)\s*}}/i'
                => '<?php if ($this->__if("$1")) { ?>',
            '/{{\s*UNLESS\s+([\w\d_.]+)\s*}}/i'
                => '<?php if ($this->__unless("$1")) { ?>',
            '/{{\s*([\w\d._]+)\s*}}/'
                => '<?php $this->__print("$1"); ?>',
        );
        static $callback_regexp = '/{{\s*(\w[\w\d_]*)\(([^)]*)\)\s*}}/';
        $this->compiled = null;
        if (empty($this->tpl)) {
            throw new \Exception('Template not loaded');
        }
        $this->compiled = '?'.'>' . preg_replace(
            array_keys($compile_regexps),
            array_values($compile_regexps),
            preg_replace_callback($callback_regexp, function($matches) {
                $method = $matches[1];
                $m_args = preg_split('/\s*,\s*/', trim($matches[2]));
                $args = array();
                foreach ($m_args as $arg) {
                    if (is_numeric($arg)) {
                        $args[] = $arg;
                    } elseif (in_array($qchar = substr($arg, 0, 1), array('"', "'"))) {
                        if ($qchar != substr($arg, -1)) {
                            throw new \Exception('Syntax error');
                        }
                        $args[] = "'" . addcslashes(stripslashes(trim($arg, '"\'')), '"\\\'') . "'";
                    } else {
                        if (!preg_match('/^[\w\d._]+$/', $arg)) {
                            throw new \Exception('Syntax error');
                        }
                        $args[] = "\$this->__getValue('$arg')";
                    }
                }
                $args = 'array('.join(", ", $args).')';
                return "<?php \$this->__dispatchCall('$method', $args); ?>";
            }, $this->tpl)
        );
    }

}
