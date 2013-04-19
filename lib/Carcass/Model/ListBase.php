<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Model;

use Carcass\Query;
use Carcass\Corelib;

/**
 * Base list model
 *
 * @package Carcass\Model
 */
abstract class ListBase implements \Iterator, \ArrayAccess, \Countable, Corelib\ExportableInterface, Corelib\RenderableInterface, Query\ListReceiverInterface {
    use QueryTrait, ListQueryTrait;

    protected function assembleQueryDispatcher() {
        return new Query\BaseDispatcher;
    }

    protected function prepareQueryDispatcher(Query\BaseDispatcher $QueryDispatcher) {
        return $this->prepareListQueryDispatcher($QueryDispatcher);
    }

    public function getRenderArray() {
        return $this->exportArray();
    }
}