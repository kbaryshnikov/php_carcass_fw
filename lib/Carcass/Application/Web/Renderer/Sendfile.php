<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_Renderer_Sendfile extends Web_Renderer_Base {

    protected $location;

    public function __construct($location) {
        $this->location = $location;
    }

    protected function sendHeaders(Web_Response $Response) {
        parent::sendHeaders($Response);
        $Response->sendInternalRedirect($this->location);
    }

}

