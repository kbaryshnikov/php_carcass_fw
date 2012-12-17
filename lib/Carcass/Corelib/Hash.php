<?php

namespace Carcass\Corelib;

class Hash implements \Iterator, \ArrayAccess, \Countable, DatasourceRefInterface, DataReceiverInterface, ExportableInterface {
    use HashTrait;

    public function __construct($init_with = null) {
        $init_with and $this->import($init_with);
    }

    public function __clone() {
        $this->deepClone();
    }
}
