<?php

namespace Carcass\Log;

class ErrorLogWriter implements WriterInterface {

    public function log(Message $Message) {
        error_log($Message->getFormattedString());
        return $this;
    }

}
