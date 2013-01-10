<?php

namespace Carcass\Rule;

class IsValidIpAddress extends Base {

    protected $ERROR = 'invalid_ip_address';

    public function validate($value) {
        return ( $value === null || $value == long2ip(ip2long($value)) );
    }

}
