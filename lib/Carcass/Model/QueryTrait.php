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
     * @var Query\Base|null
     */
    protected $Query = null;

    /**
     * @return \Carcass\Query\Base
     */
    protected function getQuery() {
        if (null === $this->Query) {
            $this->Query = $this->assembleQuery();
        }
        return $this->Query;
    }

    /**
     * @return \Carcass\Query\Base
     */
    protected function assembleQuery() {
        return new Query\Base;
    }
}