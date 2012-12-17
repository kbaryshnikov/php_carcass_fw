<?php

namespace Carcass\Corelib;

interface DatasourceInterface {

    public function get($key, $default_value = null);

    public function has($key);

}
