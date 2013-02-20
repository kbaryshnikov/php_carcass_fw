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
     * @return $this
     */
    public function write($session_id, array $data);

    /**
     * @param string $session_id
     * @return $this
     */
    public function delete($session_id);

}
