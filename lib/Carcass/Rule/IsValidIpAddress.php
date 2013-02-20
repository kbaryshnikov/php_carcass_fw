<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

/**
 * Class IsValidIpAddress
 * @package Carcass\Rule
 */
class IsValidIpAddress extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_ip_address';

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return ( $value === null || $value == long2ip(ip2long($value)) );
    }

}
