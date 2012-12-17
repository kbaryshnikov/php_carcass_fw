<?php

namespace Carcass\DevTools;

class ConsoleReporter extends BaseReporter {

    protected
        $handle = null,
        $colors_enabled = false,
        $need_to_close_handle = false;

    protected static $known_non_cli_handles = [
        'STDOUT' => 'php://output',
        'STDERR' => 'php://stderr',
    ];

    protected static $is_colorable_terminal_regexp = '#^(linux|xterm)#i';

    public function __construct($handle = null, $colors_enabled = null) {
        $this->colors_enabled = $colors_enabled === null ? static::doesSupportColors($handle) : (bool)$colors_enabled;
        if ($handle !== null) {
            if (substr($handle, 0, 3) === 'STD') {
                $this->handle = $this->getStdHandle($handle);
            } else {
                $this->handle = fopen($handle, 'a+');
            }
        }
    }

    public function dump($value) {
        $this->write(sprintf("\n%s\n", $this->formatValue($value)));
        return $this;
    }

    public function dumpException(\Exception $Exception) {
        $this->write(sprintf(
            "\n" .
            ($this->colors_enabled ? "\x1b[0m\x1b[37;41m\x1b[2K" : '') .
            "%s in file %s line %d: %s.\n" .
            ($this->colors_enabled ? "\x1b[0m\x1b[2K" : '' ).
            "Stack trace:\n\t%s" .
            "\n",
            get_class($Exception),
            $Exception->getFile(),
            $Exception->getLine(),
            $Exception->getMessage(),
            str_replace("\n", "\n\t", $Exception->getTraceAsString())
        ));
        return $this;
    }

    protected static function doesSupportColors($handle_name) {
        if (!in_array($handle_name, ['STDOUT', null], true)) {
            return false;
        }
        if (!isset($_SERVER['TERM'])) {
            return false;
        }
        return preg_match(static::$is_colorable_terminal_regexp, $_SERVER['TERM']);
    }

    protected function getStdHandle($name) {
        if (PHP_SAPI === 'cli') {
            return constant($name);
        } else if (array_key_exists($name, self::$known_non_cli_handles)) {
            $this->need_to_close_handle = true;
            return fopen(self::$known_non_cli_handles[$name], 'a');
        } else {
            throw new LogicException("Unknown destination handle: '$name'");
        }
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

}
