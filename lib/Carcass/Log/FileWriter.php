<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Log;

/**
 * FileWriter
 * @package Carcass\Log
 */
class FileWriter implements WriterInterface {

    /**
     * @var string
     */
    protected $filename;

    /**
     * @param string $filename
     */
    public function __construct($filename) {
        $this->setFilename($filename);
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @param Message $Message
     * @return $this
     */
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
