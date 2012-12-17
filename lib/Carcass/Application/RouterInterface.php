<?php

namespace Carcass\Application;

interface RouterInterface {

    public function route(Request $Request, ControllerInterface $Controller);

}
