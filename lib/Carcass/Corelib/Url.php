<?php

namespace Carcass\Corelib;

class Url {

    protected
        $parsed = null,
        $uri_template = null,
        $args = null,
        $qs_args = null,
        $unmatched_args_to_qs = false;

    public function __construct($uri_template = null, array $args = [], array $qs_args = []) {
        $this->setUriTemplate($uri_template)->setArgs($args)->setQsArgs($qs_args);
    }

    public static function constructRaw($url) {
        $self = new static;
        $self->parsed = $url;
        return $self;
    }

    public function setUriTemplate($uri_template) {
        $this->uri_template = $uri_template;
        $this->parsed = null;
        return $this;
    }

    public function setArgs(array $args) {
        $this->args = $args;
        $this->parsed = null;
        return $this;
    }

    public function setQsArgs(array $qs_args) {
        $this->qs_args = $qs_args;
        $this->parsed = null;
        return $this;
    }

    public function setUnmatchedArgsToQueryString($bool) {
        $this->unmatched_args_to_qs = (bool)$bool;
        $this->parsed = null;
        return $this;
    }

    public function addQueryString(array $qs_args) {
        $this->parsed = UrlTemplate::addQueryString($this->getParsedUri(), $qs_args);
        return $this;
    }

    public function getRelative() {
        return $this->getParsedUri();
    }

    public function __toString() {
        return $this->getParsedUri();
    }

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
