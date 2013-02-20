<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * IP v4 tools
 *
 * @package Carcass\Corelib
 */
class Ip {

    protected $address;
    protected $mask;

    /**
     * Subnet of CIDR form is possible, both separated with / and as a second argument.
     * Examples for 192.168.1.0/24:
     *   new Ip('192.168.1.0', '255.255.255.0');
     *   new Ip('192.168.1.0', 24);
     *   new Ip('192.168.1.0/255.255.255.0');
     *   new Ip('192.168.1.0/24');
     *   new Ip('192.168.1.33/24'); (address part will be normalized)
     *
     * @param string $address
     * @param string|int|null $subnet
     * @throws \InvalidArgumentException
     */
    public function __construct($address, $subnet = null) {
        $expl = explode('/', $address, 2);
        $address = $expl[0];
        if (isset($expl[1])) {
            $subnet = $expl[1];
        }

        $addr = ip2long($address);
        if (false === $address) {
            throw new \InvalidArgumentException("Invalid IP address: '$address'");
        }

        if (null === $subnet) {
            $mask = null;
        } else {
            if (is_numeric($subnet)) {
                if ($subnet < 1 || $subnet > 32) {
                    throw new \InvalidArgumentException("Invalid subnet: '$subnet'");
                }

                $mask = (-1 << (32 - (int)$subnet));
            } else {
                $mask = ip2long($subnet);
            }

            if ($mask == -1 || $mask == ip2long("255.255.255.255")) {
                $mask = null;
            } else {
                $addr &= $mask;
            }
        }


        $this->address = $addr;
        $this->mask    = $mask;
    }

    /**
     * __toString 
     * 
     * @return string represetnation, in 0.0.0.0/255.255.255.255 form
     */
    public function __toString() {
        return long2ip($this->address) . (null === $this->mask ? '' : '/' . long2ip($this->mask));
    }

    /**
     * Tests if $ip_addr equals to $this address
     * 
     * @param string|Ip $ip_addr 
     * @return bool
     */
    public function equals($ip_addr) {
        if ($ip_addr instanceof self) {
            $compare_to = $ip_addr;
        } else {
            $compare_to = new self($ip_addr);
        }
        return $this->address === $compare_to->address && $this->mask === $compare_to->mask;
    }

    /**
     * Tests if $ip_addr matches $this subnet
     * 
     * @param string|Ip $ip_addr 
     * @return bool
     */
    public function matches($ip_addr) {
        if ($ip_addr instanceof self) {
            $match_for = $ip_addr;
        } else {
            $match_for = new self($ip_addr);
        }
        if ($this->mask === null) {
            return $this->address === $match_for->address && $match_for->mask === null;
        } else {
            return $this->address === ( $match_for->address & $this->mask ) 
                && ( $match_for->mask === null || ( $this->mask === ( $this->mask & $match_for->mask ) ) );
        }
    }

}
