<?php

namespace Carcass\DevTools;

abstract class BaseReporter {

    abstract public function dump($value);

    abstract public function dumpException(\Exception $Exception);

    protected function formatValue($value) {
        $type = gettype($value);
        switch ($type) {
            case 'resource':
                $contents = print_r($value, true);
                break;
            case 'array':
            case 'object':
                $type = null;
                // no break intentionally
            default:
                $contents = print_r($value, true);
        }
        return ($type === null ? '' : "$type: ") . $contents;
    }

}
