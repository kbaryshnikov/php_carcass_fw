<?php

namespace Carcass\Corelib;

interface ResultInterface extends ExportableInterface {

    public function assign($value);

    public function bind(RenderableInterface $Object);

    public function displayTo(ResponseInterface $Response);

}
