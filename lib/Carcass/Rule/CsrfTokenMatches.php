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
 * Class CsrfTokenMatches
 * @package Carcass\Rule
 */
class CsrfTokenMatches extends Base {

    /**
     * @var string
     */
    protected $ERROR = 'invalid_csrf_token';
    /**
     * @var \Carcass\Application\Web_Session
     */
    protected $Session;
    protected $session_key;

    /**
     * @param \Carcass\Application\Web_Session $Session
     * @param $session_key
     */
    public function __construct(Application\Web_Session $Session, $session_key) {
        $this->Session     = $Session;
        $this->session_key = $session_key;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        $session_value = $this->Session->get($this->session_key);

        if (empty($session_value) || $value != $session_value) {
            return false;
        }
        return true;
    }
}
