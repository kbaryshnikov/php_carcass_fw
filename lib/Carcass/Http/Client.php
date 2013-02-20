<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Http;

use Carcass\Corelib;

/**
 * HTTP Client. Requires the curl extension.
 *
 * @package Carcass\Http
 */
class Client {

    const DEFAULT_POST_BODY_CHARSET = 'utf-8';

    protected
        $mode = null,
        $error = null,
        $status = null,
        $url = null,
        $post_body = null,
        $post_body_charset = null;

    protected $curl_options = [
        CURLOPT_HEADER         => 0,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ];

    /**
     * GET shortcut
     *
     * @param string $url
     * @param array $curl_options
     * @param null $error returned by reference
     * @param bool $treat_40x_50x_as_error
     * @return string|bool response string, or false on failure
     */
    public static function get($url, array $curl_options = array(), &$error = null, $treat_40x_50x_as_error = false) {
        /** @var Client $self */
        $self = new static;
        return $self
            ->setUrl($url)
            ->setGet()
            ->setCurlOptions($curl_options)
            ->dispatch($error, $treat_40x_50x_as_error);
    }

    /**
     * POST shortcut
     *
     * @param string $url
     * @param array|string $post_body
     * @param array $curl_options
     * @param null $error returned bu reference
     * @param bool $treat_40x_50x_as_error
     * @return string|bool response string, or false on failure
     */
    public static function post($url, $post_body, array $curl_options = array(), &$error = null, $treat_40x_50x_as_error = false) {
        /** @var Client $self */
        $self = new static;
        return $self
            ->setUrl($url)
            ->setPost($post_body)
            ->setCurlOptions($curl_options)
            ->dispatch($error, $treat_40x_50x_as_error);
    }

    /**
     * Performs the request and returns the result
     * @param null $error returned by reference
     * @param bool $treat_40x_50x_as_error
     * @return string|bool response string, or false on failure
     */
    public function dispatch(&$error = null, $treat_40x_50x_as_error = false) {
        Corelib\Assert::that('URL is not empty')->isNotEmpty($this->url);
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

        $ch = null;
        try {
            $ch = curl_init($this->url);
            curl_setopt_array($ch, $curlopts);
            $result = curl_exec($ch);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        if (isset($ch, $result)) {
            if (false === $result) {
                $this->error = curl_error($ch);
            } else {
                $this->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($treat_40x_50x_as_error && $this->status >= 400) {
                    $this->error = "{$this->status} status returned";
                    $result      = false;
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

    /**
     * @return string|null
     */
    public function getLastError() {
        return $this->error;
    }

    /**
     * @return array|null
     */
    public function getLastStatus() {
        return $this->status;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * @param array $curl_options
     * @return $this
     */
    public function setCurlOptions(array $curl_options) {
        $this->curl_options = $curl_options + $this->curl_options;
        return $this;
    }

    /**
     * @return $this
     */
    public function setGet() {
        $this->mode = null;
        return $this;
    }

    /**
     * @param array|string $post_body
     * @param string|null $post_body_charset
     * @return $this
     */
    public function setPost($post_body, $post_body_charset = null) {
        $this->mode              = CURLOPT_POST;
        $this->post_body         = is_array($post_body) ? http_build_query($post_body) : $post_body;
        $this->post_body_charset = $post_body_charset ? : self::DEFAULT_POST_BODY_CHARSET;
        return $this;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method) {
        $method          = strtoupper($method);
        $method_constant = 'CURLOPT_' . $method;
        if (defined($method_constant)) {
            $this->mode = constant($method_constant);
        } else {
            $this->mode = $method;
        }
        return $this;
    }

}
