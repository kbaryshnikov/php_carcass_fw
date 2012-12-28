<?php

namespace Carcass\Memcached;

use Carcass\Corelib;
use Carcass\Corelib\StringTemplate;
use Carcass\Corelib\ArrayTools;
use Carcass\Corelib\Assert;

class KeyBuilder extends StringTemplate {
    
    protected $config = [
        'null_value' => 'NULL',
        'escape_chars' => '.',
        'separator' => ';',
    ];

    public function __construct($template = null, array $config = []) {
        parent::__construct();
        $this->config = $config + $this->config;
        $template and $this->load($template);
    }

    public function i($int) {
        Assert::isInteger($int);
        return $int;
    }

    public function iNul($int) {
        return $this->nullOr('i', $int);
    }

    public function s($str, $extra_escape_chars = '') {
        return addcslashes((string)$str, $this->config['escape_chars'] . $extra_escape_chars);
    }

    public function sNul($str, $extra_escape_chars = '') {
        return $this->nullOr('s', $str, $extra_escape_chars);
    }

    public function f($float) {
        Assert::isNumeric($float);
        return $float;
    }

    public function fNul($float) {
        return $this->nullOr('f', $float);
    }

    public function id($id) {
        Assert::isValidId($id);
        return $id;
    }

    public function idNul($id) {
        return $this->nullOr('id', $id);
    }

    public function n($num, $decimals) {
        return number_format($num, $decimals, '.', '');
    }

    public function nNul($num, $decimals) {
        return $this->nullOr('n', $num, $decimals);
    }

    public function json($value) {
        return ArrayTools::jsonEncode($value);
    }

    public function jsonNul($value) {
        return $this->nullOr('json', $value);
    }

    public function set(array $values) {
        return $this->s(join($this->config['separator'], array_map(function($value) {
            return addcslashes($value, $this->config['separator']);
        }, $values)));
    }

    public function setNul(array $values = null) {
        return $this->nullOr('set', $values);
    }

    public function parse($args = null) {
        if ($args instanceof Corelib\ExportableInterface) {
            $args = $args->exportArray();
        }
        return parent::parse($args);
    }

    protected function nullOr($method, $value /* args */) {
        if (null === $value) {
            return $this->config['null_value'];
        }
        $args = func_get_args();
        return call_user_func_array([$this, array_shift($args)], $args);
    }

}
