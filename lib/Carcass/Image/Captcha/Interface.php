<?php

namespace Carcass\Image;

use Carcass\Application;

interface Captcha_Interface {

    public function __construct(Application\Web_Session $Session, $session_field = null);
    public function validate($entered_text);
    public function regenerate();
    public function output(Application\ResponseInterface $Response);

}
