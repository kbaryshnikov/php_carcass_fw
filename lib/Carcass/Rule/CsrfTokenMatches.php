<?php

namespace Carcass\Rule;

use Carcass\Application;

class CsrfTokenMatches extends Base {

    protected $ERROR = 'invalid_csrf_token';
    protected $Session, $session_key;
    
    public function __construct(Application\Web_Session $Session, $session_key) {
        $this->Session = $Session;
        $this->session_key = $session_key;
    }

    public function validate($value) {
        $session_value = $this->Session->get($this->session_key);

        if (empty($session_value) || $value != $session_value) {
            return false;
        }
        return true;
    }
}
