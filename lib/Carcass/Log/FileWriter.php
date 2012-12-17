<?php

namespace Carcass\Log;

class FileWriter implements WriterInterface {

    protected $filename;

    public function __construct($filename) {
        $this->setFilename($filename);
    }

    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }

    public function log(Message $Message) {
        error_log($Message->getFormattedString() . "\n", 3, $this->filename);
        return $this;
    }

}
