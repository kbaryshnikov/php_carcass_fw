<?php

namespace Carcass\Rule;

use Carcass\Image;
use Carcass\Application;

class CaptchaMatches extends Base {

    const DEFAULT_CAPTCHA_IMPL = '\\Carcass\\Image\\Captcha_Imagick';

    protected $ERROR = 'invalid_captcha';
    protected $Session;
    protected $captcha_impl = null;
    protected $session_field = null;
    
    public function __construct(Application\Web_Session $Session, $captcha_impl = null, $session_field = null) {
        $this->Session = $Session;
        $this->captcha_impl = $captcha_impl ?: self::DEFAULT_CAPTCHA_IMPL;
        $this->session_field = null;
    }
    
    public function validate($value) {
        $IC = new $this->captcha_impl($this->Session, $this->session_field);
        if (!$IC->validate($value)) {
            return false;
        }
        return true;
    }
}
