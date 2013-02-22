<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Hash is a collection of corelib traits implementing ArrayObject and core source/receiver interfaces.
 *
 * @method Hash|mixed get($key, $default_value = null)
 * @method Hash|mixed __get($key)
 *
 * @property mixed Cookie
 * @package Carcass\Corelib
 */
class Hash implements \Iterator, \ArrayAccess, \Countable, DatasourceRefInterface, DataReceiverInterface, ExportableInterface, ImportableInterface {
    use HashTrait;

    /**
     * @param null $init_with
     */
    public function __construct($init_with = null) {
        $init_with and $this->import($init_with);
    }

    public function __clone() {
        $this->deepClone();
    }

}
