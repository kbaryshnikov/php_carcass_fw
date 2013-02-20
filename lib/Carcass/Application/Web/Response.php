<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;
use Carcass\Http;

/**
 * Web response implementation. Collects data for HTTP headers, sends on commit.
 *
 * @package Carcass\Application
 */
class Web_Response extends Corelib\Response {

    const COOKIE_MAX_RELATIVE_VALUE       = 999999999;
    const INTERNAL_REDIRECT_PSEUDO_STATUS = -1;

    /**
     * @var int
     */
    protected $status = 200;
    /**
     * @var string|null HTTP protocol version (e.g. '1.0'), or null for CGI interface
     */
    protected $http_protocol = null;
    /**
     * @var bool
     */
    protected $headers_sent = false;
    /**
     * @var string
     */
    protected $internal_redirect_header = 'X-Accel-Redirect';
    /**
     * @var null
     */
    protected $redirect = null;
    /**
     * @var array
     */
    protected $headers = [];
    /**
     * @var array
     */
    protected $cookies = [];
    /**
     * @var array
     */
    protected $cookie_settings = [
        'expire'   => 0,
        'path'     => '/',
        'domain'   => null,
        'secure'   => false,
        'httponly' => true,
    ];

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param bool $no_auto_buffering
     */
    public function __construct(Corelib\Request $Request, $no_auto_buffering = false) {
        $this->configureFromEnv($Request->Env);
        $no_auto_buffering or $this->begin();
    }

    /**
     * @param \Carcass\Corelib\DatasourceInterface $Env
     * @return $this
     */
    public function configureFromEnv(Corelib\DatasourceInterface $Env) {
        if ($Env->has('HOST')) {
            $this->cookie_settings['domain'] = '.' . preg_replace('#www\.#', '', $Env->get('HOST'));
        }
        if ($Env->get('SCHEME') === 'https') {
            $this->cookie_settings['secure'] = true;
        }
        if ($Env->has('SERVER_PROTOCOL') && preg_match('#^HTTP/(\d+\.\d+)#', $Env->get('SERVER_PROTOCOL'), $matches)) {
            $this->http_protocol = $matches[1];
        }
        return $this;
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function setCookieSettings(array $settings) {
        $this->cookie_settings = $settings + $this->cookie_settings;
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function sendHeader($name, $value) {
        $this->headers[$name][] = $value;
        $this->is_buffering or $this->sendHeaders();
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @param int|null $expire INF for "lifetime" cookie, int: relative value if less than COOKIE_MAX_RELATIVE_VALUE, absolute value otherwise
     * @param array $options
     * @return $this
     */
    public function sendCookie($name, $value, $expire = null, array $options = []) {
        if (null !== $expire) {
            if ($expire === INF) {
                $expire = Http\Definitions::COOKIE_LIFETIME_FOREVER;
            } elseif ($expire > 0 && $expire <= static::COOKIE_MAX_RELATIVE_VALUE) {
                $expire = Corelib\TimeTools::getTime() + $expire;
            }
            $options['expire'] = $expire;
        }
        $this->cookies[$name] = [$value, $options];
        return $this;
    }

    /**
     * @param string $location
     * @param int $status
     * @return $this
     * @throws \InvalidArgumentException
     */
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

    /**
     * @param string $location
     * @return $this
     */
    public function sendPermanentRedirect($location) {
        return $this->sendRedirect($location, 301);
    }

    /**
     * @param string $location
     * @return $this
     */
    public function sendInternalRedirect($location) {
        $this->headers[$this->internal_redirect_header] = [$location];
        return $this;
    }

    /**
     * Sends $status and displays a HTTP error stub message
     *
     * @param int $status
     * @param string|null $title
     * @param string|null $message
     * @return $this
     */
    public function writeHttpError($status = 500, $title = null, $message = null) {
        if ($this->is_buffering) {
            $this->rollback();
        }
        $this->setStatus($status);
        $this->write($this->formatHttpError($title, $message));
        return $this;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status) {
        if (!$status) {
            $status = 200;
        } else {
            $status = intval($status);
            Http\Definitions::validateStatusCode($status);
        }
        $this->status = $status;
        return $this;
    }

    /**
     * @param string $header_name
     * @return $this
     */
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
        $this->headers  = [];
        $this->cookies  = [];
    }

    /**
     * @throws \LogicException
     */
    protected function sendHeaders() {
        if ($this->headers_sent) {
            throw new \LogicException("Headers already sent");
        }
        header(Http\Definitions::getStatusHeader($this->status, $this->http_protocol));
        if ($this->redirect && static::isRedirectStatus($status)) {
            header('Location: ' . $this->redirect);
        }
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value");
            }
        }
        foreach ($this->cookies as $name => $values) {
            list ($value, $options) = $values;
            $options = (array)$options + $this->cookie_settings;
            setcookie($name, $value, $options['expire'], $options['path'], $options['domain'], $options['secure'], $options['httponly']);
        }
    }

    /**
     * @return string
     */
    protected function getStatusText() {
        return Http\Definitions::getStatusText($this->status);
    }

    /**
     * @param int $status
     * @return bool
     */
    protected static function isRedirectStatus($status) {
        return in_array($status, [301, 302, 303, 307]);
    }

    /**
     * @param string $title
     * @param string $message
     * @return string
     */
    protected function formatHttpError($title, $message) {
        $status  = $this->getStatus();
        $title   = htmlspecialchars($title ? : $this->getStatusText());
        $message = nl2br(htmlspecialchars($message ? : $this->getStatusText()));
        $now     = Corelib\TimeTools::formatTime(DATE_RFC2822);
        return "<DOCTYPE html>\n<html><head><title>$status $title</title></head>"
            . "<body><h1>$status $title</h1><hr /><code>$message</code><hr />$now</body></html>";
    }

}
