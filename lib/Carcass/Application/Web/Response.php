<?php

namespace Carcass\Application;

use Carcass\Corelib as Corelib;

class Web_Response extends Corelib\Response {

    const
        COOKIE_MAX_RELATIVE_VALUE = 999999999,
        COOKIE_LIFETIME_FOREVER   = 2147483647,
        INTERNAL_REDIRECT_PSEUDO_STATUS = -1;

    protected
        $status = 200,
        $http_protocol = null,
        $headers_sent = false,
        $internal_redirect_header = 'X-Accel-Redirect',
        $redirect = null,
        $headers = [],
        $cookies = [],
        $cookie_settings = [
            'expire'   => 0,
            'path'     => '/',
            'domain'   => null,
            'secure'   => false,
            'httponly' => true,
        ];

    public function __construct(Request $Request, $no_auto_buffering = false) {
        $this->configureFromEnv($Request->Env);
        $no_auto_buffering or $this->begin();
    }

    public function configureFromEnv(Corelib\DatasourceInterface $Env) {
        if ($Env->has('HTTP_HOST')) {
            $this->cookie_settings['domain'] = '.' . preg_replace('#www\.#', '', trim($Env->get('HTTP_HOST', '.')));
        }
        if ($Env->has('HTTPS') && 0 != strcasecmp($Env->HTTPS, 'off')) {
            $this->cookie_settings['secure'] = true;
        }
        if ($Env->has('SERVER_PROTOCOL') && preg_match('#^HTTP/(\d+\.\d+)#', $Env->SERVER_PROTOCOL, $matches)) {
            $this->http_protocol = $matches[1];
        }
        return $this;
    }

    public function setCookieSettings(array $settings) {
        $this->cookie_settings = $settings + $this->cookie_settings;
        return $this;
    }

    public function sendHeader($name, $value) {
        $this->headers[$name][] = $value;
        $this->is_buffering or $this->sendHeaders();
        return $this;
    }

    public function sendCookie($name, $value, $expire = null, array $options = []) {
        if (null !== $expire) {
            if ($expire === INF || $expire === +INF) {
                $expire = static::COOKIE_MAX_EXPIRE;
            } elseif ($expire > 0 && $expire <= static::COOKIE_MAX_RELATIVE_VALUE) {
                $expire = Corelib\TimeTools::getTime() + $expire;
            }
            $options['expire'] = $expire;
        }
        $this->cookies[$name] = [$value, $options];
        return $this;
    }

    public function sendRedirect($location, $status = 302) {
        if ($status === static::INTERNAL_REDIRECT_PSEUDO_STATUS) {
            return $this->sendInternalRedirect($location);
        }
        if (!static::isRedirectStatus($status)) {
            throw new \InvalidArgumentException("Invalid redirect status: '$status'");
        }
        $this->setStatus($status);
        $this->redirect = $location;
        return $this;
    }

    public function sendPermanentRedirect($location) {
        return $this->sendRedirect($location, 301);
    }

    public function sendInternalRedirect($location) {
        $this->headers[$this->internal_redirect_header] = [$location];
        return $this;
    }

    public function writeHttpError($status = 500, $title = null, $message = null) {
        if ($this->buffering) {
            $this->rollback();
        }
        $this->setStatus(500);
        $this->write($this->formatHttpError($title, $message));
        return $this;
    }

    public function setStatus($status = null) {
        if ($status === null) {
            $status = 200;
        } else {
            $status = intval($status);
            if ($status < 100 || $status >= 600) {
                throw new \InvalidArgumentException("Invalid HTTP status: $status");
            }
        }
        $this->status = $status;
        return $this;
    }

    public function setInternalRedirectHeader($header_name) {
        $this->internal_redirect_header = $header_name;
        return $this;
    }

    public function commit() {
        parent::commit([$this, 'sendHeaders']);
        $this->resetHeaders();
    }

    public function rollback() {
        parent::rollback();
        $this->resetHeaders();
    }

    protected function resetHeaders() {
        $this->redirect = null;
        $this->headers = [];
        $this->cookies = [];
    }

    protected function sendHeaders() {
        if (!$this->headers_sent) {
            throw new \LogicException("Headers already sent");
        }
        $status = $this->getStatus();
        if ($this->http_protocol) {
            header("HTTP/{$this->http_protocol} $status " . $this->getStatusText());
        } else {
            header("Status: $status");
        }
        if ($this->redirect && static::isRedirectStatus($status)) {
            header('Location: ' . $this->redirect);
        }
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value");
            }
        }
        foreach ($cookies as $name => $values) {
            list ($value, $options) = $values;
            $options = (array)$options + $this->cookie_settings;
            setcookie($name, $value, $options['expire'], $options['path'], $options['domain'], $options['secure'], $options['httponly']);
        }
    }

    protected function getStatusText() {
        return isset(static::$rfc_statuses[$this->status]) ? static::$rfc_statuses[$this->status] : 'Unknown';
    }

    protected static function isRedirectStatus($status) {
        return in_array($status, [301, 302, 303, 307]);
    }

    protected function formatHttpError($title, $message) {
        $status = $this->getStatus();
        $title = htmlspecialchars($title ?: $this->getStatusText());
        $message = nl2br(htmlspecialchars($message ?: $this->getStatusText()));
        $now = Corelib\TimeTools::formatTime(DATE_RFC2822);
        return "<DOCTYPE html>\n<html><head><title>$status $title</title></head>"
             . "<body><h1>$status $title</h1><hr /><code>$message</code><hr />$now</body></html>";
    }

    protected static $rfc_statuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created', 
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content', 
        205 => 'Reset Content', 
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
    ];

}
