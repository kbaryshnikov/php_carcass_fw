<?php

namespace Carcass\Mysql;

use Carcass\Corelib;
use  Carcass\Corelib\StringTemplate;
use Carcass\Corelib\JsonTools;
use Carcass\Corelib\TimeTools;
use Carcass\Corelib\Assert;

class QueryTemplate extends StringTemplate {

    protected
        $QueryParser,
        $globals = [],
        $date_format     = 'Y-m-d',
        $time_format     = 'H:i:s',
        $datetime_format = 'Y-m-d H:i:s';

    public function __construct(QueryParser $QueryParser, $template) {
        parent::__construct();
        $this->QueryParser = $QueryParser;
        $this->load($template);
    }

    public function registerGlobals(array $globals) {
        $this->globals = $globals;
        return $this;
    }

    public function i($int) {
        Assert::that('value is integer')->isInteger($int);
        return $int;
    }

    public function iNul($int) {
        return $this->nullOr('i', $int);
    }

    public function s($str) {
        return "'" . $this->QueryParser->escapeString($str) . "'";
    }

    public function sNul($str, $extra_escape_chars = '') {
        return $this->nullOr('s', $str, $extra_escape_chars);
    }

    public function f($float) {
        Assert::that('value is numeric')->isNumeric($float);
        return $float;
    }

    public function fNul($float) {
        return $this->nullOr('f', $float);
    }

    public function id($id) {
        Assert::that('value is a valid id')->isValidId($id);
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

    public function b($value) {
        return $value ? 'TRUE' : 'FALSE';
    }

    public function bNul($value) {
        return $this->nullOr('b', $value);
    }

    public function json($value) {
        return JsonTools::encode($value);
    }

    public function jsonNul($value) {
        return $this->nullOr('json', $value);
    }

    public function limit($limit, $offset = 0) {
        $tokens = [];
        if ($limit > 0) {
            $tokens[] = 'LIMIT ' . $this->lim($limit);
        }
        if ($offset > 0) {
            $tokens[] = 'OFFSET ' . $this->lim($offset);
        }
        return join(' ', $tokens);
    }

    public function lim($i, $default = 0) {
        if ($i < 1) {
            $i = $default;
        }
        return max(0, (int)$i);
    }

    public function name($name) {
        return join('.', array_map(function($n) {
            return '`' . str_replace('`', '``', $n) . '`';
        }, explode('.', $name)));
    }

    public function in(array $values, $escape_method = 's') {
        if (!count($values)) {
            throw new \InvalidArgumentException("Cannot use in() with no values");
        }
        $result_items = [];
        foreach ($values as $value) {
            $result_items[] = $this->$escape_method($value);
        }
        return '(' . join(',', $result_items) . ')';
    }

    public function inStr(array $values) {
        return $this->in($values, 's');
    }

    public function inInt(array $values) {
        return $this->in($values, 'i');
    }

    public function inId(array $values) {
        return $this->in($values, 'id');
    }

    public function now() {
        return $this->s( $this->getFormattedDateTime() );
    }

    public function unixtime() {
        return TimeTools::getTime();
    }

    public function datetime($unixtime) {
        return $this->s( $this->getFormattedDateTime($unixtime) );
    }

    public function date($unixtime) {
        return $this->s( $this->getFormattedDate($unixtime) );
    }

    public function time($unixtime) {
        return $this->s( $this->getFormattedTime($unixtime) );
    }

    public function datetimeNul($unixtime) {
        return $this->nullOr($unixtime, 'datetime');
    }

    public function dateNul($unixtime) {
        return $this->nullOr($unixtime, 'date');
    }

    public function timeNul($unixtime) {
        return $this->nullOr($unixtime, 'time');
    }

    public function parse($args = null) {
        $this->cleanAll();
        if ($args instanceof Corelib\ExportableInterface) {
            $args = $args->exportArray();
        }
        $globals = $this->globals + array_filter($args, 'is_scalar');
        if ($globals) {
            $this->setGlobals($globals);
        }
        return parent::parse($args);
    }

    protected function nullOr(/** @noinspection PhpUnusedParameterInspection */ $method, $value /* args */) {
        if (null === $value) {
            return 'NULL';
        }
        $args = func_get_args();
        return call_user_func_array([$this, array_shift($args)], $args);
    }

    protected function getFormattedDate($unixtime = null) {
        return TimeTools::formatTime($this->date_format, $unixtime);
    }

    protected function getFormattedDateTime($unixtime = null) {
        return TimeTools::formatTime($this->datetime_format, $unixtime);
    }

    protected function getFormattedTime($unixtime = null) {
        return TimeTools::formatTime($this->time_format, $unixtime);
    }

}
