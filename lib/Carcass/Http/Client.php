<?php

namespace Carcass\Http;

use Carcass\Corelib;

class Client {

    const
        DEFAULT_POST_BODY_CHARSET = 'utf-8';

    protected
        $mode = null,
        $error = null,
        $status = null,
        $url = null,
        $post_body = null,
        $post_body_charset = null,
        $curl_options = array(
            CURLOPT_HEADER          => 0,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
        );

    public static function get($url, array $curl_options = array(), &$error = null, $treat_40x_50x_as_error = false) {
        return static::construct()
            ->setUrl($url)
            ->setGet()
            ->setCurlOptions($curl_options)
            ->dispatch($error, $treat_40x_50x_as_error);
    }

    public static function post($url, $post_body, array $curl_options = array(), &$error = null, $treat_40x_50x_as_error = false) {
        return static::construct()
            ->setUrl($url)
            ->setPost($post_body)
            ->setCurlOptions($curl_options)
            ->dispatch($error, $treat_40x_50x_as_error);
    }

    public function dispatch(&$error = null, $treat_40x_50x_as_error = false) {
        Corelib\Assert::isNotEmpty($this->url);
        $curlopts = $this->curl_options;

        if (null !== $this->mode) {
            if (is_string($this->mode)) {
                $curlopts[CURLOPT_CUSTOMREQUEST] = $this->mode;
            } else {
                $curlopts[$this->mode] = true;
            }
        }
        if ($this->mode === CURLOPT_POST) {
            $curlopts[CURLOPT_POSTFIELDS] = $this->post_body;
            $curlopts[CURLOPT_HTTPHEADER] = array(
                'Content-Type: application/x-www-form-urlencoded; charset=' . $this->post_body_charset,
                'Cache-Control: no-cache',
            );
        }

        $this->error = null;

        try {
            $ch = curl_init($this->url);
            curl_setopt_array($ch, $curlopts); 
            $result = curl_exec($ch);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        if (isset($result)) {
            if (false === $result) {
                $this->error = curl_error($ch);
            } else {
                $this->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($treat_40x_50x_as_error && $this->status >= 400) {
                    $this->error = "{$this->status} status returned";
                    $result = false;
                }
            }
        } else {
            $result = false;
            if (empty($this->error)) {
                $this->error = 'Unexpected error';
            }
        }

        if (!empty($ch)) {
            curl_close($ch);
        }

        $error = $this->error;
        return $result;
    }

    public function getLastError() {
        return $this->error;
    }

    public function getLastStatus() {
        return $this->status;
    }

    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    public function setCurlOptions(array $curl_options) {
        $this->curl_options = $curl_options + $this->curl_options;
        return $this;
    }

    public function setGet() {
        $this->mode = null;
        return $this;
    }

    public function setPost($post_body, $post_body_charset = null) {
        $this->mode = CURLOPT_POST;
        $this->post_body = is_array($post_body) ? http_build_query($post_body) : $post_body;
        $this->post_body_charset = $post_body_charset ?: self::DEFAULT_POST_BODY_CHARSET;
        return $this;
    }

    public function setMethod($method) {
        $method = strtoupper($method);
        $method_constant = 'CURLOPT_' . $method;
        if (defined($method_constant)) {
            $this->mode = constant($method_constant);
        } else {
            $this->mode = $method;
        }
        return $this;
    }

}
