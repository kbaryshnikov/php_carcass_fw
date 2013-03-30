<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Model;

use Carcass\Query;

/**
 * Query methods trait
 *
 * @package Carcass\Model
 */
trait QueryTrait {

    /**
     * @var Query\BaseDispatcher
     */
    protected $QueryDispatcher = null;

    /**
     * @return \Carcass\Query\BaseDispatcher
     */
    protected function getQueryDispatcher() {
        if (null === $this->QueryDispatcher) {
            $this->QueryDispatcher = $this->assembleQueryDispatcher();
        }
        return $this->QueryDispatcher;
    }

    /**
     * @return \Carcass\Query\BaseDispatcher
     */
    protected function assembleQueryDispatcher() {
        return new Query\BaseDispatcher;
    }
}