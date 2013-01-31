<?php

namespace Carcass\Corelib;

interface ResultInterface extends ExportableInterface {

    public function assign($key, $value);

    public function bind($Object);

    public function displayTo(ResponseInterface $Response);

}
