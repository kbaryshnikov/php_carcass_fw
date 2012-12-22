<?php

namespace Carcass\Application;

class Web_RouterInterface extends RouterInterface {

    public function getUrl($route, array $args);

    public function getAbsoluteUrl($route, array $args);

}
