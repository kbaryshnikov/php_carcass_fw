<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_Renderer_Redirect extends Web_Renderer_Base {

    protected $url;

    public function __construct($url, $status = 302) {
        $this->url = $url;
        $this->setStatus($status);
    }

    protected function sendHeaders(Web_Response $Response) {
        parent::sendHeaders($Response);
        $Response->sendRedirect($this->url);
    }

}
