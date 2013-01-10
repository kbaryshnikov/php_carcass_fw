<?php

namespace Carcass\Rule;

class IsAbsoluteUrl extends Base {

    protected $ERROR = 'invalid_url';

    public function validate($value) {
        return null === $value || preg_match(
            '[^((https?):\/\/)?([a-z]([a-z0-9\-]*\.)+([a-z]{2,6}))(\/[a-z0-9_\-\.~]*)*(\?[a-z0-9+_\-\.%=\&:;,]*)?(#[a-z0-9_-]*)?$]i', 
            $value
        );
    }
}
