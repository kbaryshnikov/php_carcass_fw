<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Application;

use Carcass\Corelib;

/**
 * Cli application response
 * @package Carcass\Application
 */
class Cli_Response extends Corelib\Response {

    /**
     * @var int
     */
    protected $status = 0;

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status) {
        parent::setStatus(intval($status));
        return $this;
    }

}
