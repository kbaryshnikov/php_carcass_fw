<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Log;

/**
 * ErrorLogWriter
 * @package Carcass\Log
 */
class ErrorLogWriter implements WriterInterface {

    /**
     * @param Message $Message
     * @return $this
     */
    public function log(Message $Message) {
        error_log($Message->getFormattedString());
        return $this;
    }

}
