<?php

namespace Carcass\Field;

use Carcass\Filter;

trait FilterTrait {

    protected
        $filters = [];

    public function clearFilters() {
        $this->filters = [];
    }

    public function addFilter($filter) {
        if (!$filter instanceof Filter\FilterInterface) {
            $filter = Filter\Factory::assemble((array)$filter);
        }
        $this->filters[] = $filter;
        return $this;
    }

    public function setFilters(array $filters) {
        foreach ($filters as $Filter) {
            $this->addFilter($Filter);
        }
        return $this;
    }

    protected function filterValue($value) {
        foreach ($this->filters as $Filter) {
            $Filter->filter($value);
        }
        return $value;
    }

}
