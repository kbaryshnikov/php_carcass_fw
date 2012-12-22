<?php

namespace Carcass\Application;

class Web_Router_Factory {

    public static function assembleConfigRouter() {
        return new Web_Router_Config(Instance::getConfig()->web->routes);
    }

}
