<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Emulates the Blitz class from blitz 0.6 extension.
 * Emulation is incomplete (e.g. no fetch), but enough for internal use as string template parsers.
 */
class BlitzLiteEmulator {

    protected
        $set = array(),
        $globals = array(),
        $ctx = array(),
        $context = null,
        $stack = array(),
        $compiled = null,
        $__count = null,
        $__pos = null,
        $result = null,
        $tpl = null;

    /**
     * @param null $file
     */
    public function __construct($file = null) {
        if ($file !== null) {
            $this->loadFile($file);
        }
    }

    /**
     * @param $string
     * @return void
     */
    public function load($string) {
        $this->tpl = $string;
        $this->compiled = null;
        $this->clean();
    }

    /**
     * @param $string
     * @return void
     */
    public function loadFile($string) {
        $this->tpl = file_get_contents($string);
        $this->compiled = null;
        $this->clean();
    }

    /**
     * @return void
     */
    public function clean() {
        $this->set = array();
        $this->result = null;
    }

    /**
     * @return void
     */
    public function cleanGlobals() {
        $this->globals = array();
        $this->result = null;
    }

    /**
     * @param array $set
     * @return void
     */
    public function set(array $set) {
        $this->set = $set + $this->set;
        $this->result = null;
    }

    /**
     * @param array $globals
     * @return void
     */
    public function setGlobals(array $globals) {
        $this->globals = $globals + $this->globals;
        $this->result = null;
    }

    /**
     * @param array $set
     * @return string
     */
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

    /**
     * @param array $set
     * @return void
     */
    public function display(array $set = null) {
        if ($set !== null) {
            $this->set($set);
        }
        if ($this->result === null) {
            $this->parse();
        }
        print $this->result;
    }

    /**
     * @param $addr
     * @return bool|int|string
     */
    protected function __getValue($addr) {
        if (is_numeric($addr)) {
            return $addr;
        }
        foreach (array($this->context, $this->globals) as $ptr) {
            if ($this->__findValueIn($ptr, $addr)) {
                return $ptr;
            }
        }
        return false;
    }

    /**
     * @param $ptr
     * @param $addr
     * @return bool
     */
    protected function __findValueIn(&$ptr, $addr) {
        foreach (explode('.', trim($addr)) as $k) {
            if (isset($ptr[$k])) {
                $ptr = $ptr[$k];
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * @return void
     */
    protected function __push() {
        array_push($this->stack, $this->context);
        array_push($this->stack, $this->__pos);
        array_push($this->stack, $this->__count);
    }

    /**
     * @return void
     */
    protected function __pop() {
        $this->__count  = array_pop($this->stack);
        $this->__pos    = array_pop($this->stack);
        $this->context  = array_pop($this->stack);
    }

    /**
     * @param $addr
     * @return array
     */
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

    /**
     * @param $addr
     */
    protected function __print($addr) {
        $ptr = $this->__getValue($addr);
        if (is_scalar($ptr) && false !== $ptr) {
            print $ptr;
        }
    }

    /**
     * @param $addr
     * @return array|bool|null
     */
    protected function __special($addr) {
        if (substr($addr, 0, 1) != '_') {
            return null;
        }
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

    /**
     * @param $addr
     * @return array|bool|null
     */
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

    /**
     * @param $addr
     * @return bool
     */
    protected function __unless($addr) {
        return !$this->__if($addr);
    }

    protected function __ctxEnd() {
        $this->__pop();
    }

    /**
     * @return string
     */
    protected function __execute() {
        $this->context = $this->set;
        $this->stack = array();
        ob_start();
        eval($this->compiled);
        return ob_get_clean();
    }

    /**
     * @param $method
     * @param array $args
     * @throws \Exception
     */
    protected function __dispatchCall($method, array $args) {
        if (method_exists($this, $method)) {
            print call_user_func_array(array($this, $method), $args);
        } else {
            throw new \Exception("Undefined method: '$method'");
        }
    }

    /**
     * @throws \Exception
     */
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

