<?php

namespace Carcass\Application;

use Carcass\Corelib as Corelib;

class Web_SessionStorage_MemcachedCas extends Web_SessionStorage_Memcached {

    const
        TRY_SESSION_SAVE_LIMIT = 10;

    protected 
        $cas_tokens = [];

    public function get($session_id) {
        $data = $this->getDataFromMemcached($session_id, $cas_token);
        $this->setCasToken($session_id, $cas_token);
        return $data;
    }
    
    public function write($session_id, array $data, $was_changed_ignored) {
        $cas_token          = $this->getCasToken($session_id);
        $mc_key             = $this->getMcacheKey($session_id);
        $attempts_count     = 0;

        do {
            $session_data_changed_by_another_process = false;
            if (null === $cas_token) {
                $result = $this->Memcached->add(
                    $mc_key,
                    $data,
                    $this->mc_expire
                );
                if (false === $result) {
                    $session_data_changed_by_another_process = true;
                }
            } else {
                $result = $this->Memcached->cas(
                    $cas_token,
                    $mc_key,
                    $data, 
                    $this->mc_expire
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
    }

    public function delete($session_id) {
        $this->Memcached->delete($this->getMcacheKey($session_id));
        unset($this->cas_tokens[$session_id]);
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
        return is_array($data) ? $data : [];
    }

}
