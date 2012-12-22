<?php

namespace Carcass\Corelib;

interface ViewInterface {

    public function assign($key, $value);

    public function bind($Object);

    public function render();

    public function displayTo(ResponseInterface $Response);

}
