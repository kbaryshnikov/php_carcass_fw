<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * URL with templates support
 *
 * @package Carcass\Corelib
 */
class Url {

    protected $parsed = null;
    protected $uri_template = null;
    protected $args = null;
    protected $qs_args = null;
    protected $unmatched_args_to_qs = false;

    /**
     * @param string|null $uri_template
     * @param array $args
     * @param array $qs_args
     */
    public function __construct($uri_template = null, array $args = [], array $qs_args = []) {
        $this->setUriTemplate($uri_template);
        $this->setArgs($args);
        $this->setQsArgs($qs_args);
    }

    /**
     * @param string $url
     * @return Url
     */
    public static function constructRaw($url) {
        /** @var Url $self */
        $self = new static;
        $self->parsed = $url;
        return $self;
    }

    /**
     * @param string $uri_template
     * @return $this
     */
    public function setUriTemplate($uri_template) {
        $this->uri_template = $uri_template;
        $this->parsed = null;
        return $this;
    }

    /**
     * @param array $args
     * @return $this
     */
    public function setArgs(array $args) {
        $this->args = $args;
        $this->parsed = null;
        return $this;
    }

    /**
     * @param array $qs_args
     * @return $this
     */
    public function setQsArgs(array $qs_args) {
        $this->qs_args = $qs_args;
        $this->parsed = null;
        return $this;
    }

    /**
     * If this setting is enabled, template arguments with no matches in template will be appended to the query string.
     *
     * @param bool $enable
     * @return $this
     */
    public function setUnmatchedArgsToQueryString($enable = true) {
        $this->unmatched_args_to_qs = (bool)$enable;
        $this->parsed = null;
        return $this;
    }

    /**
     * @param array $qs_args
     * @return $this
     */
    public function addQueryString(array $qs_args) {
        $this->parsed = UrlTemplate::addQueryString($this->getParsedUri(), $qs_args);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getRelative() {
        return $this->getParsedUri();
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string)$this->getParsedUri();
    }

    /**
     * @param string $hostname
     * @param string $scheme
     * @param string|null $user
     * @param string|null $password
     * @return string
     */
    public function getAbsolute($hostname, $scheme = 'http', $user = null, $password = null) {
        $result = $scheme . '://';
        if ($user) {
            $result .= $user;
            if ($password) {
                $result .= ':' . $password;
            }
            $result .= '@';
        }
        $result .= $hostname . $this->getRelative();
        return $result;
    }

    /**
     * @return string
     */
    protected function getParsedUri() {
        if ($this->parsed === null) {
            $this->parsed = $this->parseUrl();
        }
        return $this->parsed;
    }

    protected function parseUrl() {
        return UrlTemplate::build($this->uri_template, $this->args, $this->qs_args, $this->unmatched_args_to_qs);
    }

}
