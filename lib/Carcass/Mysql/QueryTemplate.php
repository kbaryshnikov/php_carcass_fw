<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mysql;

use Carcass\Corelib;
use Carcass\Corelib\StringTemplate;
use Carcass\Corelib\JsonTools;
use Carcass\Corelib\TimeTools;
use Carcass\Corelib\Assert;

/**
 * Query template parser, based on StringTemplate
 *
 * @package Carcass\Mysql
 */
class QueryTemplate extends StringTemplate {

    protected
        $QueryParser,
        $globals = [],
        $date_format = 'Y-m-d',
        $time_format = 'H:i:s',
        $datetime_format = 'Y-m-d H:i:s';

    /**
     * @param QueryParser $QueryParser
     * @param string $template
     */
    public function __construct(QueryParser $QueryParser, $template) {
        parent::__construct();
        $this->QueryParser = $QueryParser;
        $this->load($template);
    }

    /**
     * @param array $globals
     * @return $this
     */
    public function registerGlobals(array $globals) {
        $this->globals = $globals;
        return $this;
    }

    /**
     * @param $int
     * @return mixed
     */
    public function i($int) {
        Assert::that('value is integer')->isInteger($int);
        return $int;
    }

    /**
     * @param $str
     * @return string
     */
    public function s($str) {
        return "'" . $this->QueryParser->escapeString($str) . "'";
    }

    /**
     * @param $float
     * @return string
     */
    public function f($float) {
        Assert::that('value is numeric')->isNumeric($float);
        return (string)$float;
    }

    /**
     * @param $id
     * @return string
     */
    public function id($id) {
        Assert::that('value is a valid id')->isValidId($id);
        return $id;
    }

    /**
     * @param $num
     * @param $decimals
     * @return string
     */
    public function n($num, $decimals) {
        return number_format($num, $decimals, '.', '');
    }

    /**
     * @param $value
     * @return string
     */
    public function b($value) {
        return $value ? 'TRUE' : 'FALSE';
    }

    /**
     * @param $value
     * @return string
     */
    public function json($value) {
        return $this->s(JsonTools::encode($value));
    }

    /**
     * Alias for json call
     *
     * @param $value
     * @return string
     */
    public function jsonEncode($value) {
        return $this->json($value);
    }

    /**
     * @param $limit
     * @param $offset
     * @return string
     */
    public function limit($limit, $offset = 0) {
        $tokens = [];
        if ($limit > 0) {
            $tokens[] = 'LIMIT ' . $this->lim($limit);
        } elseif ($offset > 0) {
            $tokens[] = 'LIMIT 18446744073709551615';
        }
        if ($offset > 0) {
            $tokens[] = 'OFFSET ' . $this->lim($offset);
        }
        return join(' ', $tokens);
    }

    /**
     * @param $i
     * @param $default
     * @return int
     */
    public function lim($i, $default = 0) {
        if ($i < 1) {
            $i = $default;
        }
        return max(0, (int)$i);
    }

    /**
     * @param $name
     * @return string
     */
    public function name($name) {
        return join(
            '.', array_map(
                function ($n) {
                    return '`' . str_replace('`', '``', $n) . '`';
                }, explode('.', $name)
            )
        );
    }

    /**
     * @param array $values
     * @param string $escape_method
     * @return string
     * @throws \InvalidArgumentException
     */
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

    /**
     * @param array $values
     * @return string
     */
    public function inStr(array $values) {
        return $this->in($values, 's');
    }

    /**
     * @param array $values
     * @return string
     */
    public function inInt(array $values) {
        return $this->in($values, 'i');
    }

    /**
     * @param array $values
     * @return string
     */
    public function inId(array $values) {
        return $this->in($values, 'id');
    }

    /**
     * @return string
     */
    public function now() {
        return $this->s($this->getFormattedDateTime());
    }

    /**
     * @return int
     */
    public function unixtime() {
        return TimeTools::getTime();
    }

    /**
     * @param $unixtime
     * @return string
     */
    public function datetime($unixtime) {
        return $this->s($this->getFormattedDateTime($unixtime));
    }

    /**
     * @param $unixtime
     * @return string
     */
    public function date($unixtime) {
        return $this->s($this->getFormattedDate($unixtime));
    }

    /**
     * @param $unixtime
     * @return string
     */
    public function time($unixtime) {
        return $this->s($this->getFormattedTime($unixtime));
    }

    /**
     * 'Like' pattern builder
     *
     * Example: {{ like('?1%', prefix) }}
     *
     * @param string $template   'like' template; ?1 ?2 etc = placeholder for argument(s)
     * @internal param vaargs arguments, _ % will be escaped
     * @return string
     */
    public function like($template /* ... */) {
        $args = func_get_args();
        array_shift($args);
        array_walk(
            $args, function ($s) {
                return addcslashes($s, '%_');
            }
        );
        return $this->s(
            preg_replace_callback(
                '/\?(\d)/',
                function ($m) use ($args) {
                    $idx = $m[1] - 1;
                    return isset($args[$idx]) ? $args[$idx] : '';
                },
                $template
            )
        );
    }

    /**
     * Set argument builder.
     * Builds a comma-separated mysql SET value from $items
     * keys with non-empty values.
     *
     * @param array $items   ex. [ 'foo' => true, 'bar' => 1, 'none' => false, 'none2' => null ]
     * @return string        ex. 'foo,bar'
     */
    public function set(array $items) {
        return $this->s(join(',', array_keys(array_filter($items))));
    }

    /**
     * @param array|Corelib\ExportableInterface $args
     * @return string
     */
    public function parse($args = []) {
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

    /**
     * @param $method  <method>Nul or <method>Def
     * @param array $args
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, array $args) {
        static $null_value_map = [
            'nul' => self::NULL_VALUE,
            'def' => self::DEFAULT_VALUE,
        ];
        if (preg_match('/^(?<fn>.*)(?<mod>Nul|Def)$/i', $method, $matches) && method_exists($this, $matches['fn'])) {
            if (!count($args)) {
                throw new \BadMethodCallException("$method requires at least 1 argument");
            }
            $null_value = $null_value_map[strtolower($matches['mod'])];
            return null === reset($args) ? $null_value : call_user_func_array([$this, $matches['fn']], $args);
        }
        throw new \BadMethodCallException("Unknown method: $method");
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

    const NULL_VALUE = 'NULL';
    const DEFAULT_VALUE = 'DEFAULT';

}
