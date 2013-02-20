<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * DatasourceInterface
 * @package Carcass\Corelib
 */
interface DatasourceInterface {

    /**
     * @param mixed $key
     * @param mixed $default_value
     * @return mixed
     */
    public function get($key, $default_value = null);

    /**
     * @param mixed $key
     * @return bool
     */
    public function has($key);

}
