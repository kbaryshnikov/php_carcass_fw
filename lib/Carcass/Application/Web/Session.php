<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;

/**
 * Web Session implementation.
 *
 * @method mixed get($key, $default_value = null)
 * @method Web_Session set($key, $value)
 * @method Web_Session delete($key)
 * @package Carcass\Application
 */
class Web_Session {

    const DEFAULT_COOKIE_NAME = 's';
    const DEFAULT_COOKIE_LIFETIME = 0;

    /**
     * @var bool|null
     */
    protected $user_agent_supports_cookies = null;
    /**
     * @var Web_Session_StorageInterface|null
     */
    protected $PersistentStorage = null;
    /**
     * @var string
     */
    protected $cookie_name = self::DEFAULT_COOKIE_NAME;
    /**
     * @var int
     */
    protected $cookie_lifetime = self::DEFAULT_COOKIE_LIFETIME;
    /**
     * @var bool
     */
    protected $send_ident_to_user_agent = true;
    /**
     * @var null
     */
    protected $session_id = null;
    /**
     * @var \Carcass\Corelib\Request
     */
    protected $Request;
    /**
     * @var Web_Response
     */
    protected $Response;
    /**
     * @var \Carcass\Corelib\Hash
     */
    protected $Data;

    /**
     * @param \Carcass\Corelib\Request $Request
     * @param Web_Response $Response
     * @param Web_Session_StorageInterface $PersistentStorage
     */
    public function __construct(Corelib\Request $Request, Web_Response $Response, Web_Session_StorageInterface $PersistentStorage = null) {
        $this->cookie_name = self::DEFAULT_COOKIE_NAME;
        $this->cookie_lifetime = self::DEFAULT_COOKIE_LIFETIME;

        $this->Request = $Request;
        $this->Response = $Response;

        $this->Data = new Corelib\Hash;

        $PersistentStorage and $this->setPersistentStorage($PersistentStorage);
    }

    /**
     * @param Web_Session_StorageInterface $PersistentStorage
     * @return $this
     */
    public function setPersistentStorage(Web_Session_StorageInterface $PersistentStorage) {
        $this->PersistentStorage = $PersistentStorage;
        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function enableSendingSessionIdentifiersToUserAgent($bool = true) {
        $this->send_ident_to_user_agent = (bool)$bool;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableSendingSessionIdentifiersToUserAgent() {
        return $this->enableSendingSessionIdentifiersToUserAgent(false);
    }

    /**
     * @param $method
     * @param array $args
     * @return mixed
     * @throws \LogicException
     */
    public function __call($method, array $args) {
        if (method_exists($this->Data, $method)) {
            $this->ensureSessionIsStarted();
            $result = call_user_func_array([$this->Data, $method], $args);
            if ($result === $this->Data) {
                $result = $this;
            }
            return $result;
        }
        throw new \LogicException("Method '$method' is not implemented");
    }

    /**
     * @return $this
     */
    public function save() {
        $this->ensureSessionIsStarted();
        if (null !== $this->PersistentStorage) {
            $this->PersistentStorage->write($this->session_id, $this->Data->exportArray(), $this->Data->isTainted());
            $this->Data->untaint();
        }
        $this->sendSessionIdCookie();
        return $this;
    }

    /**
     * @return $this
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
     * @param $cookie_name
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setCookieName($cookie_name) {
        $cookie_name = (string)$cookie_name;
        if (empty($cookie_name)) {
            throw new \InvalidArgumentException("Invalid cookie name: '$cookie_name'");
        }
        $this->cookie_name = $cookie_name;
        return $this;
    }

    /**
     * Sets the session cookie lifetime.
     *
     * @param integer|float $cookie_lifetime Cookie lifetime: 0 for pure session cookie, expiration unix timestamp, or INF for unlimited
     * @return $this
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
     * Returns the session identifier
     * @return string
     */
    public function getSessionId() {
        return $this->session_id;
    }

    /**
     * @param $session_id
     * @return $this
     */
    public function setSessionId($session_id) {
        if (!$session_id) {
            $session_id = $this->generateSessionId();
        }
        $this->session_id = $session_id;
        $this->loadDataFromPersistentStorage();
        return $this;
    }

    /**
     * Fills $Receiver with session identifier
     *
     * @param mixed $Receiver
     * @param bool $force force setting sid parameters even if cookies are supported by user agent
     * @return $this
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

        $this->loadDataFromPersistentStorage();
    }

    protected function loadDataFromPersistentStorage() {
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

    /**
     * @param $session_id
     * @return bool
     */
    protected function isValidSessionId($session_id) {
        return is_string($session_id) && preg_match('/^[a-zA-Z0-9_-]{22}$/', $session_id);
    }

    protected function loadSessionIdFromRequest() {
        $sources_to_try = array('Cookies', 'Vars', 'Args');
        $result = null;
        $found_in = null;

        foreach ($sources_to_try as $source) {
            if ($this->Request->has($source) && $this->Request->get($source)->has($this->cookie_name)) {
                $value = $this->Request->get($source)->get($this->cookie_name);
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
