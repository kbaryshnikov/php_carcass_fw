<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Less;

/**
 * LESS Cacher Interface
 * @package Carcass\Less
 */
interface Cacher_Interface {

    /**
     * @param string $key
     * @param null $default_value
     * @return mixed
     */
    public function get($key, $default_value = null);

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function put($key, $value);

}
