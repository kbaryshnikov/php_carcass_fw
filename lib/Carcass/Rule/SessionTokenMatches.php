<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

use Carcass\Application;

/**
 * Class SessionTokenMatches
 * @package Carcass\Rule
 */
class SessionTokenMatches extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_session_token';
    /**
     * @var \Carcass\Application\Web_Session
     */
    protected $Session;
    /**
     * @var string
     */
    protected $session_key;

    /**
     * @param \Carcass\Application\Web_Session $Session
     * @param string $session_key
     */
    public function __construct(Application\Web_Session $Session, $session_key) {
        $this->Session = $Session;
        $this->session_key = $session_key;
    }

    /**
     * @param $field_value_array
     * @return bool
     */
    public function validate($field_value_array) {
        if (!is_array($field_value_array) || empty($field_value_array)) {
            return false;
        }

        reset($field_value_array);
        list($field_token_key, $field_token_value) = each($field_value_array);

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
