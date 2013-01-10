<?php

namespace Carcass\Rule;

class IsValidDomainName extends Base {

    protected
        $ERROR = 'invalid_value',
        $allow_partial = false;

    public function __construct($allow_partial = false) {
        $this->allow_partial = $allow_partial;
    }

    public function validate($value) {
        if (null === $value) {
            return true;
        }
        if ($this->allow_partial) {
            $regexp = '/^(?:[a-z0-9-]{1,63}|(?:[a-z0-9-]{1,63}\.){1,126}[a-z0-9-]{2,63})$/i';
        } else {
            $regexp = '/^(?:[a-z0-9-]{1,63}\.){1,126}[a-z0-9-]{2,63}$/i';
        }
        return strlen($value) <= 253 && preg_match($regexp, $value);
    }

}
