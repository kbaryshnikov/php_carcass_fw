<?php

namespace Carcass\Less;

interface Cacher_Interface {

    public function get($key);

    public function put($key, $value);

}
