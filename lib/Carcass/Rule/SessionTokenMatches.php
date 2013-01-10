<?php

namespace Carcass\Rule;

use Carcass\Application;

class SessionTokenMatches extends Base {

    protected $ERROR = 'invalid_session_token';
    protected $Session, $session_key;
    
    public function __construct(Application\Web_Session $Session, $session_key) {
        $this->Session = $Session;
        $this->session_key = $session_key;
    }
    
    public function validate($field_value_array) {
        @list($field_token_key, $field_token_value) = each($field_value_array);

        if (empty($field_token_key) || empty($field_token_value)) {
            return false;
        }

        $form_tokens = $this->Session->get($this->session_key);

        if (!isset($form_tokens[$field_token_key]) || $form_tokens[$field_token_key]['value'] != $field_token_value) {
            return false;
        }

        unset($form_tokens[$field_token_key]);
        $this->Session->set($this->session_key, $form_tokens);
        return true;
    }
}
