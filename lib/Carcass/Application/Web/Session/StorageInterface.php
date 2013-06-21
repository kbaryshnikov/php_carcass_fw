<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

/**
 * Session storage interface
 *
 * @package Carcass\Application
 */
interface Web_Session_StorageInterface {

    /**
     * @param string $session_id
     * @return mixed
     */
    public function get($session_id);

    /**
     * @param string $session_id
     * @param array $data
     * @param bool $is_changed
     * @return $this
     */
    public function write($session_id, array $data, $is_changed);

    /**
     * @param string $session_id
     * @return $this
     */
    public function delete($session_id);

    /**
     * Returns session id bound to current bind_uid
     *
     * @param string $bind_uid
     * @return string|null
     */
    public function getBoundSid($bind_uid);

    /**
     * Updates the session id bound to current bind_uid
     *
     * @param string $bind_uid
     * @param string|null $session_id
     * @return $this
     */
    public function setBoundSid($bind_uid, $session_id);

}
