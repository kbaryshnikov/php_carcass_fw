<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Image;

use Carcass\Application;

/**
 * Captcha_Interface
 * @package Carcass\Image
 */
interface Captcha_Interface {

    /**
     * @param \Carcass\Application\Web_Session $Session
     * @param string|null $session_field
     */
    public function __construct(Application\Web_Session $Session, $session_field = null);

    /**
     * @param string $entered_text
     * @return bool
     */
    public function validate($entered_text);

    /**
     * @return $this
     */
    public function regenerate();

    /**
     * @param \Carcass\Application\Web_Response $Response
     * @return $this
     */
    public function output(Application\Web_Response $Response);

}
