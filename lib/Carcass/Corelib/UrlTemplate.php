<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Url templates compiler
 *
 * @package Carcass\Corelib
 */
class UrlTemplate {

    protected $tpl = '';
    protected $args = [];
    protected $qs_args = [];
    protected $set_unmatched_args_to_qs = false;

    protected function __construct() {
        // pass
    }

    protected function __clone() {
        // pass
    }

    /**
     * @param string $url_template
     * @param array $args
     * @param array $qs_args
     * @param bool $set_unmatched_args_to_qs
     * @return mixed
     */
    public static function build($url_template, array $args = [], array $qs_args = [], $set_unmatched_args_to_qs = false) {
        /** @var UrlTemplate $self */
        $self = new static;
        $self->tpl = $url_template;
        $self->args = $args;
        $self->qs_args = $qs_args;
        $self->set_unmatched_args_to_qs = (bool)$set_unmatched_args_to_qs;
        return $self->buildUrl();
    }

    /**
     * @param string $url_template
     * @return array
     * @throws \LogicException
     */
    public static function compile($url_template) {
        if (substr($url_template, 0, 1) != '/') {
            $url_template = '/' . $url_template;
        }
        if (!preg_match('~^(/[^\[{]*)(.*)?$~', $url_template, $matches)) {
            throw new \LogicException("url template parser failed, this should never happen");
        }
        $prefix = $matches[1];
        $suffix_pattern = empty($matches[2]) ? null : $matches[2];
        if ($suffix_pattern) {
            $suffix_regexp = static::compilePatternToRegexp($suffix_pattern);
        } else {
            $suffix_regexp = '';
        }
        return [$prefix, $suffix_regexp];
    }

    /**
     * @param string $pattern
     * @return string
     * @throws \RuntimeException
     */
    protected static function compilePatternToRegexp($pattern) {
        $regexp = preg_replace(
            array_keys(static::$var_type_regexps),
            array_values(static::$var_type_regexps),
            static::parseOptionals( addcslashes($pattern, '.?()^') )
        );
        if (preg_match_all('/{.*}/', $regexp, $matches)) {
            throw new \RuntimeException("Incorrect selections: " . join(', ', $matches[0]));
        }
        return '#^' . $regexp . '$#';
    }

    /**
     * @param string|array $in
     * @return mixed
     */
    protected static function parseOptionals($in) {
        if (is_array($in)) {
            $in = '(?:' . substr($in[1], 1, -1) . ')?';
        }
        return preg_replace_callback('/(?P<pn>\[((?>[^\[\]]+)|(?P>pn))*\])/', [get_called_class(), __FUNCTION__], $in);
    }

    /**
     * @return string
     */
    protected function buildUrl() {
        $filled_tpl = preg_replace_callback(array_keys(static::$var_type_regexps), array($this, 'fillTemplateVarCallback'), $this->tpl);
        $cleaned_tpl = preg_replace(array('/\[[^\]]*\x00[^\]]*\]/', '/[\[\]]/'), array('', ''), $filled_tpl);
        if ($this->set_unmatched_args_to_qs) {
            $this->qs_args += $this->args;
        }
        if ($this->qs_args) {
            $qs = array();
            foreach ($this->qs_args as $var => $tmpl) {
                if (!is_scalar($tmpl)) {
                    unset($this->args[$var]);
                    continue;
                }
                $qs[$var] = preg_replace_callback(array_keys(static::$var_type_regexps), array($this, 'fillTemplateVarCallback'), $tmpl);
            }
            $cleaned_tpl = static::addQueryString($cleaned_tpl, $qs);
        }
        return $cleaned_tpl;
    }

    /**
     * @param string $str
     * @param array $qs
     * @return string
     */
    public static function addQueryString($str, array $qs = array()) {
        $str = strtok((string)$str, '?');

        $old_qs = strtok('?');
        if ($old_qs) {
            parse_str($old_qs, $old_qs_array);
            $qs += $old_qs_array;
        }

        if (isset($qs['#'])) {
            $anchor = (string)$qs['#'];
            unset($qs['#']);
        }

        if ($qs) {
            ksort($qs);
            foreach ($qs as $k => $v) {
                if (false === $v) {
                    unset($qs[$k]);
                }
            }
            $query_string = http_build_query($qs);
            if ($query_string) {
                $str .= "?$query_string";
            }
        }

        if (!empty($anchor)) {
            $str .= "#$anchor";
        }

        return $str;
    }

    /**
     * @param string|array $in
     * @return string
     */
    protected function fillTemplateVarCallback($in) {
        $in[1] = strtolower($in[1]);
        $escape_method = self::$var_type_castings[$in[1]];
        if ($special_value = $this->getSpecialVar($in[1], $in[2])) {
            $escaped_value = call_user_func($escape_method, $special_value);
        } else {
            if (!isset($this->args[$in[2]])) {
                return "\x00";
            }
            $escaped_value = call_user_func($escape_method, $this->args[$in[2]]);
            unset($this->args[$in[2]]);
        }
        return $escaped_value;
    }

    /**
     * @param string $type
     * @param string $key
     * @return int|null|string
     */
    protected function getSpecialVar($type, $key) {
        switch ($key) {
            case '_random':
                return $type === '#' ? Crypter::getRandomNumber() : Crypter::getRandomString();                
            default:
                return null;
        }
    }

    protected static function encodeIntElement($i) {
        return max(0, number_format($i, 0, '', ''));
    }

    protected static function encodeUriElement($s) {
        return rawurlencode(trim($s));
    }

    protected static
        $var_type_regexps = [
            '/{\s*(#)\s*(\w+)\s*}/Ui' => '(?P<$2>\d+)',
            '/{\s*(\$)\s*(\w+)\s*}/Ui' => '(?P<$2>[^/]+)',
            '/{\s*(\+)\s*(\w+)\s*}/Ui' => '(?P<$2>.+)',
            '/{\s*(\*)\s*(\w+)\s*}/Ui' => '(?P<$2>.*)',
        ],
        $var_type_castings = [
            '#' => array(__CLASS__, 'encodeIntElement'),
            '$' => array(__CLASS__, 'encodeUriElement'),
            '+' => 'trim',
            '*' => 'trim',
        ];

}
