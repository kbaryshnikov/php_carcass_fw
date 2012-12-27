<?php

namespace Carcass\Config;

use Carcass\Corelib;

interface ItemInterface extends Corelib\DatasourceInterface {

    public function getPath($path, $default_value = null);

    public function exportArrayFrom($path, $default_value = []);

    public function exportHashFrom($path, $default_value = []);

}
