<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_Session {

    const
        DEFAULT_COOKIE_NAME = 's',
        DEFAULT_COOKIE_LIFETIME = 0;

    protected
        $user_agent_supports_cookies    = null,
        $PersistentStorage              = null,
        $cookie_name                    = self::DEFAULT_COOKIE_NAME,
        $cookie_lifetime                = self::DEFAULT_COOKIE_LIFETIME,
        $send_ident_to_user_agent       = true,
        $session_id                     = null,
        $Request,
        $Response,
        $Data;

    public function __construct(Corelib\Request $Request, Web_Response $Response, Web_SessionStorage_Interface $PersistentStorage = null) {
        $this->cookie_name = self::DEFAULT_COOKIE_NAME;
        $this->cookie_lifetime = self::DEFAULT_COOKIE_LIFETIME;

        $this->Request = $Request;
        $this->Response = $Response;

        $this->Data = new Corelib\Hash;

        $PersistentStorage and $this->setPersistentStorage($PersistentStorage);
    }

    public function setPersistentStorage(Web_SessionStorage_Interface $PersistentStorage) {
        $this->PersistentStorage = $PersistentStorage;
        return $this;
    }

    public function enableSendingSessionIdentifiersToUserAgent($bool = true) {
        $this->send_ident_to_user_agent = (bool)$bool;
        return $this;
    }

    public function disableSendingSessionIdentifiersToUserAgent() {
        return $this->enableSendingSessionIdentifiersToUserAgent(false);
    }

    public function __call($method, array $args) {
        if (method_exists($this->Data, $method)) {
            $this->ensureSessionIsStarted();
            return call_user_func_array([$this->Data, $method], $args);
        }
        throw new \LogicException("Method '$method' is not implemented");
    }

    /**
     * @return void
     */
    public function save() {
        $this->ensureSessionIsStarted();
        if ($this->Data->isTainted() && null !== $this->PersistentStorage) {
            $this->PersistentStorage->write($this->session_id, $this->Data->exportArray());
            $this->Data->untaint();
        }
        $this->sendSessionIdCookie();
        return $this;
    }

    /**
     * @return void
     */
    public function destroy() {
        $this->ensureSessionIsStarted();
        if (null !== $this->PersistentStorage) {
            $this->PersistentStorage->delete($this->session_id);
        }
        $this->Data->clear();
        $this->generateSessionId();
        return $this;
    }

    /**
     * @param string $cookie_name
     * @return Carcass_Session
     */
    public function setCookieName($cookie_name) {
        $cookie_name = (string)$cookie_name;
        if (empty($cookie_name)) {
            throw new InvalidArgumentException("Invalid cookie name: '$cookie_name'");
        }
        $this->cookie_name = $cookie_name;
        return $this;
    }

    /**
     * Sets the session cookie lifetime.
     *
     * @param integer|INF $cookie_lifetime Cookie lifetime: 0 for pure session cookie, expiration unix timestamp, or INF for unlimited
     * @return Carcass_Session
     */
    public function setCookieExpire($cookie_lifetime) {
        if ($cookie_lifetime !== INF) {
            $cookie_lifetime = (int)$cookie_lifetime;
        }
        $this->cookie_lifetime = $cookie_lifetime;
        return $this;
    }

    /**
     * @param bool $force return non-empty result even if user agent cookies support is not detected and send_ident_to_user_agent is off
     * @return array ( cookie name => session_id ), or empty array() if cookies not supported by user agent or send_ident_to_user_agent is on
     */
    public function getIdentifier($force = false) {
        $this->ensureSessionIsStarted();

        if ($force || (!$this->user_agent_supports_cookies && $this->send_ident_to_user_agent)) {
            return [$this->cookie_name => $this->session_id];
        }

        return [];
    }

    /**
     * Returns the current session identifier
     * @return string
     */
    public function getSessionId() {
        return $this->session_id;
    }

    /**
     * Fills $Receiver with session identifier
     * 
     * @param mixed $Receiver 
     * @param bool $force force setting sid parameters even if cookies are supported by user agent
     * @return void
     */
    public function fillSessionIdParametersTo($Receiver, $force = false) {
        $Receiver->setSessionId($this->getIdentifier($force));
        return $this;
    }

    protected function ensureSessionIsStarted() {
        if (!empty($this->session_id)) {
            return;
        }

        if (!$this->loadSessionIdFromRequest()) {
            $this->generateSessionId();
        }

        $this->Data->clear();
        if ($this->PersistentStorage !== null) {
            $session_data = $this->PersistentStorage->get($this->session_id);
            $this->Data->import($session_data);
            $this->Data->untaint();
        }
    }

    protected function sendSessionIdCookie() {
        if ($this->send_ident_to_user_agent) {
            $this->Response->sendCookie($this->cookie_name, $this->session_id, $this->cookie_lifetime);
        }
    }

    protected function isValidSessionId($session_id_to_check) {
        return is_string($session_id_to_check) && preg_match('/^[a-zA-Z0-9_-]{22}$/', $session_id_to_check);
    }

    protected function loadSessionIdFromRequest() {
        $sources_to_try = array('Cookies', 'Vars', 'Args');
        $result         = null;
        $found_in       = null;

        foreach ($sources_to_try as $source) {
            if ($this->Request->has($source) && $this->Request->$source->has($this->cookie_name)) {
                $value = $this->Request->$source->get($this->cookie_name);
                if ($this->isValidSessionId($value)) {
                    $found_in = $source;
                    $result = $value;
                    break;
                }
            }
        }

        $this->user_agent_supports_cookies = ($found_in === 'Cookies');

        return $this->session_id = $result;
    }

    protected function generateSessionId() {
        return $this->session_id = Corelib\StringTools::webSafeBase64Encode(Corelib\Crypter::getRandomBytes(16));
    }

}
