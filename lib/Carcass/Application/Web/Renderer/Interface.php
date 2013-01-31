<?php

namespace Carcass\Application;

use Carcass\Corelib;

interface Web_Renderer_Interface {

    public function setStatus($status);

    public function set(Corelib\ExportableInterface $RenderData);

    public function render($force_rerender = false);

    public function displayTo(Web_Response $Response);

}
