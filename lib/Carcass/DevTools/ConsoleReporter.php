<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\DevTools;

/**
 * ConsoleReporter
 * @package Carcass\DevTools
 */
class ConsoleReporter extends BaseReporter {

    /**
     * @var resource|null
     */
    protected $handle = null;
    /**
     * @var bool
     */
    protected $colors_enabled = false;
    /**
     * @var bool
     */
    protected $need_to_close_handle = false;

    /**
     * @var array
     */
    protected static $known_non_cli_handles = [
        'STDOUT' => 'php://output',
        'STDERR' => 'php://stderr',
    ];

    /**
     * @var string
     */
    protected static $is_colorable_terminal_regexp = '#^(linux|xterm)#i';

    /**
     * @param string|null $handle
     * @param bool|null $colors_enabled
     */
    public function __construct($handle = null, $colors_enabled = null) {
        $this->colors_enabled = $colors_enabled === null ? static::doesSupportColors($handle) : (bool)$colors_enabled;
        if ($handle !== null) {
            if (0 == strncasecmp('std', $handle, 3)) {
                $this->handle = $this->getStdHandle($handle);
            } else {
                $this->handle = fopen($handle, 'a+');
            }
        }
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function dump($value) {
        $this->write(sprintf("\n%s\n", $this->formatValue($value)));
        return $this;
    }

    /**
     * @param \Exception $Exception
     * @return $this
     */
    public function dumpException(\Exception $Exception) {
        $this->write(
            sprintf(
                "\n" .
                    ($this->colors_enabled ? "\x1b[0m\x1b[37;41m\x1b[2K" : '') .
                    "%s in file %s line %d: %s.\n" .
                    ($this->colors_enabled ? "\x1b[0m\x1b[2K" : '') .
                    "Stack trace:\n\t%s" .
                    "\n",
                get_class($Exception),
                $Exception->getFile(),
                $Exception->getLine(),
                $Exception->getMessage(),
                str_replace("\n", "\n\t", $Exception->getTraceAsString())
            )
        );
        return $this;
    }

    /**
     * @param string $handle_name
     * @return bool
     */
    protected static function doesSupportColors($handle_name) {
        if (!in_array($handle_name, ['STDOUT', null], true)) {
            return false;
        }
        if (!isset($_SERVER['TERM'])) {
            return false;
        }
        return preg_match(static::$is_colorable_terminal_regexp, $_SERVER['TERM']) ? true : false;
    }

    /**
     * @param $name
     * @return resource
     * @throws \LogicException
     */
    protected function getStdHandle($name) {
        $name = strtoupper($name);
        if (PHP_SAPI === 'cli') {
            if (defined($name)) {
                return constant($name);
            }
        } elseif (array_key_exists($name, self::$known_non_cli_handles)) {
            $this->need_to_close_handle = true;
            return fopen(self::$known_non_cli_handles[$name], 'a');
        }
        throw new \LogicException("Unknown destination handle: '$name'");
    }

    protected function write($s) {
        if ($this->handle) {
            fwrite($this->handle, $s);
        } else {
            print $s;
        }
    }

    public function __destruct() {
        if ($this->handle && $this->need_to_close_handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function finalize() {
        $this->write("---------------------------------------------------------------------------------------------------------\n");
    }

}
