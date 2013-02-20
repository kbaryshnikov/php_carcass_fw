<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Rule;

use Carcass\Image;
use Carcass\Application;

/**
 * CaptchaMatches Rule
 * @package Carcass\Rule
 */
class CaptchaMatches extends Base {

    const DEFAULT_CAPTCHA_IMPL = '\Carcass\Image\Captcha_Imagick';

    /**
     * @var string
     */
    protected $ERROR = 'invalid_captcha';
    /**
     * @var \Carcass\Application\Web_Session
     */
    protected $Session;
    /**
     * @var null|string
     */
    protected $captcha_impl = null;
    /**
     * @var null|string
     */
    protected $session_field = null;

    /**
     * @param \Carcass\Application\Web_Session $Session
     * @param string|null $captcha_impl
     * @param string|null $session_field
     */
    public function __construct(Application\Web_Session $Session, $captcha_impl = null, $session_field = null) {
        $this->Session = $Session;
        $this->captcha_impl = $captcha_impl ?: self::DEFAULT_CAPTCHA_IMPL;
        $this->session_field = null;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function validate($value) {
        $captcha_impl = $this->captcha_impl;
        /** @var Image\Captcha_Interface $IC  */
        $IC = new $captcha_impl($this->Session, $this->session_field);
        if (!$IC->validate($value)) {
            return false;
        }
        return true;
    }
}
