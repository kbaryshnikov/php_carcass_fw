<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

use Carcass\Filter;

/**
 * Filter methods implementation for fields
 *
 * @package Carcass\Field
 */
trait FilterTrait {

    protected $filters = [];

    /**
     * @return $this
     */
    public function clearFilters() {
        $this->filters = [];
        return $this;
    }

    /**
     * @param $filter
     * @return $this
     */
    public function addFilter($filter) {
        if (!$filter instanceof Filter\FilterInterface) {
            $filter = Filter\Factory::assemble((array)$filter);
        }
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function setFilters(array $filters) {
        foreach ($filters as $Filter) {
            $this->addFilter($Filter);
        }
        return $this;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function filterValue($value) {
        foreach ($this->filters as $Filter) {
            /** @var Filter\FilterInterface $Filter */
            $Filter->filter($value);
        }
        return $value;
    }

}
