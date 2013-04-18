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
 * Memcached session storage with CAS-based checks used to merge
 * changes made by parallel requests.
 *
 * @package Carcass\Application
 */
class Web_Session_MemcachedCasStorage extends Web_Session_MemcachedStorage {

    const TRY_SESSION_SAVE_LIMIT = 10;

    /**
     * @var array
     */
    protected $cas_tokens = [];

    /**
     * @param string $session_id
     * @return array
     */
    public function get($session_id) {
        $data = $this->getDataFromMemcached($session_id, $cas_token);
        $this->setCasToken($session_id, $cas_token);
        return $data;
    }

    /**
     * @param string $session_id
     * @param array $data
     * @throws \LogicException
     * @return $this
     */
    public function write($session_id, array $data) {
        $cas_token          = $this->getCasToken($session_id);
        $mc_key             = $this->getMcacheKey($session_id);
        $attempts_count     = 0;

        do {
            $session_data_changed_by_another_process = false;
            if (null === $cas_token) {
                $result = $this->Memcached->add(
                    $mc_key,
                    $data,
                    0,
                    $this->mc_expire
                );
                if (false === $result) {
                    $session_data_changed_by_another_process = true;
                }
            } else {
                $result = $this->Memcached->cas(
                    $mc_key,
                    $data,
                    0,
                    $this->mc_expire,
                    $cas_token
                );
                if (false === $result) {
                    $session_data_changed_by_another_process = true;
                }
            }
            if ($session_data_changed_by_another_process) {
                // cas check or add() failed, the session has been modified since we have read it.
                // we read it again, try to merge data and set.
                $existing_data = $this->getDataFromMemcached($session_id, $cas_token);
                if (!empty($existing_data)) {
                    Corelib\ArrayTools::mergeInto($data, $existing_data); 
                }
            }
            if (++$attempts_count > self::TRY_SESSION_SAVE_LIMIT) {
                throw new \LogicException('TRY_SESSION_SAVE_LIMIT exceeded in ' . __METHOD__);
            }
        } while (false !== $session_data_changed_by_another_process);

        $this->setCasToken($session_id, $cas_token);
        return $this;
    }

    /**
     * @param string $session_id
     * @return $this
     */
    public function delete($session_id) {
        $this->Memcached->delete($this->getMcacheKey($session_id));
        unset($this->cas_tokens[$session_id]);
        return $this;
    }

    protected function getCasToken($session_id) {
        return isset($this->cas_tokens[$session_id]) ? $this->cas_tokens[$session_id] : null;
    }

    protected function setCasToken($session_id, $cas_token) {
        $this->cas_tokens[$session_id] = $cas_token;
    }

    protected function getDataFromMemcached($session_id, &$cas_token = null) {
        $key = $this->getMcacheKey($session_id);
        $data = $this->Memcached->get($key, null, $cas_token);

        $args = $this->Memcached->getLastArgs();
        $cas_token = isset($args[2]) ? $args[2] : null;

        return is_array($data) ? $data : [];
    }

}
