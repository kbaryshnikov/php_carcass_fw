<?php

namespace Carcass\Corelib;

trait ExportableTrait {

    public function exportArray() {
        $result = [];
        foreach ($this->getDataArrayPtr() as $key => $value) {
            $result[$key] = $value instanceof ExportableInterface ? $value->exportArray() : $value;
        }
        return $result;
    }

}
