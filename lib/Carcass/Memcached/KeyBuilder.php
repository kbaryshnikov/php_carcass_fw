<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Memcached;

use Carcass\Corelib;
use /** @noinspection PhpUndefinedClassInspection */
    Carcass\Corelib\StringTemplate;
use Carcass\Corelib\JsonTools;
use Carcass\Corelib\Assert;

/**
 * Memcached Key Builder
 * @package Carcass\Memcached
 */
/** @noinspection PhpUndefinedClassInspection */
class KeyBuilder extends StringTemplate {

    /**
     * @var array
     */
    protected $config = [
        'null_value'   => 'NULL',
        'escape_chars' => '.',
        'separator'    => ';',
    ];

    /**
     * @var string
     */
    protected $template = null;

    /**
     * @param $template
     * @param array $config
     */
    public function __construct($template = null, array $config = []) {
        /** @noinspection PhpUndefinedClassInspection */
        parent::__construct();
        $this->config = $config + $this->config;
        $this->template = $template;
        $template and $this->load($template);
    }

    /**
     * @return null|string
     */
    public function getTemplate() {
        return $this->template;
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
     * @param $int
     * @return mixed
     */
    public function iNul($int) {
        return $this->nullOr('i', $int);
    }

    /**
     * @param $str
     * @param string $extra_escape_chars
     * @return string
     */
    public function s($str, $extra_escape_chars = '') {
        return addcslashes((string)$str, $this->config['escape_chars'] . $extra_escape_chars);
    }

    /**
     * @param $str
     * @param string $extra_escape_chars
     * @return mixed
     */
    public function sNul($str, $extra_escape_chars = '') {
        return $this->nullOr('s', $str, $extra_escape_chars);
    }

    /**
     * @param $float
     * @return mixed
     */
    public function f($float) {
        Assert::that('value is numeric')->isNumeric($float);
        return $float;
    }

    /**
     * @param $float
     * @return mixed
     */
    public function fNul($float) {
        return $this->nullOr('f', $float);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function id($id) {
        Assert::that('value is a valid id')->isValidId($id);
        return $id;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function idNul($id) {
        return $this->nullOr('id', $id);
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
     * @param $num
     * @param $decimals
     * @return mixed
     */
    public function nNul($num, $decimals) {
        return $this->nullOr('n', $num, $decimals);
    }

    /**
     * @param $value
     * @return string
     */
    public function json($value) {
        return JsonTools::encode($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function jsonNul($value) {
        return $this->nullOr('json', $value);
    }

    /**
     * @param array $values
     * @return string
     */
    public function set(array $values) {
        return $this->s(
            join(
                $this->config['separator'], array_map(
                    function ($value) {
                        return addcslashes($value, $this->config['separator']);
                    }, $values
                )
            )
        );
    }

    /**
     * @param array $values
     * @return mixed
     */
    public function setNul(array $values = null) {
        return $this->nullOr('set', $values);
    }

    /**
     * @param $args
     * @return string
     */
    public function parse($args = null) {
        if ($args instanceof Corelib\ExportableInterface) {
            $args = $args->exportArray();
        }
        /** @noinspection PhpUndefinedClassInspection */
        return parent::parse($args);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function limit($limit, $offset = 0) {
        return '[' . ( $this->lim($limit, 0) ?: '' ). ':' . $this->lim($offset) . ']';
    }

    /**
     * @param $i
     * @param int $default
     * @return int
     */
    public function lim($i, $default = 0) {
        if ($i < 1) {
            $i = $default;
        }
        return max(0, (int)$i);
    }

    /**
     * @param $method
     * @param $value
     * @return mixed
     */
    protected function nullOr(/** @noinspection PhpUnusedParameterInspection */
        $method, $value /* args */) {
        if (null === $value) {
            return $this->config['null_value'];
        }
        $args = func_get_args();
        return call_user_func_array([$this, array_shift($args)], $args);
    }

}
