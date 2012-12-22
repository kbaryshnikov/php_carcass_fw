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
        if (defined('STDOUT') && $this->filename === STDOUT) { 
            fprintf(STDOUT, $Message->getFormattedString() . "\n");
        } elseif (defined('STDERR') && $this->filename === STDERR) {
            fprintf(STDERR, $Message->getFormattedString() . "\n");
        } else {
            error_log($Message->getFormattedString() . "\n", 3, $this->filename);
        }
        return $this;
    }

}
