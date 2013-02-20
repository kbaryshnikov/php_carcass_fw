<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Log;

/**
 * Log Writer Interface
 * @package Carcass\Log
 */
interface WriterInterface {

    /**
     * @param Message $Message
     * @return $this
     */
    public function log(Message $Message);

}
