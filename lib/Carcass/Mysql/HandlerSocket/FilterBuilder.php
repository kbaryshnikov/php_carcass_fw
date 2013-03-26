<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mysql;

use Carcass\Corelib\ExportableInterface;

class HandlerSocket_FilterBuilder implements ExportableInterface {

    protected $filters = [];
    protected $fcols = [];

    public function __construct(array $fcols) {
        $this->fcols = $fcols;
    }

    public function addFilter($col, $op, $value) {
        $this->validateField($col);
        $this->filters[] = ['F', $op, $col, $value];
        return $this;
    }

    public function addWhile($col, $op, $value) {
        $this->validateField($col);
        $this->filters[] = ['W', $op, $col, $value];
        return $this;
    }

    public function exportArray() {
        return $this->filters;
    }

    protected function validateField($col) {
        if (is_int($col)) {
            if (isset($this->fcols[$col])) {
                return;
            }
        }
        if (false !== array_search($col, $this->fcols)) {
            return;
        }
        throw new \InvalidArgumentException("Index does not have filter column '$col'");
    }

}