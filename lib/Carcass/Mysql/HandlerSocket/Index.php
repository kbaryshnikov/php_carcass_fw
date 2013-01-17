<?php

namespace Carcass\Mysql;

class HandlerSocket_Index {

    protected
        $Connection,
        $index_id,
        $cols,
        $index_connect_cmd;

    public function __construct(HandlerSocket_Connection $Connection, $index_id, array $cols, $index_connect_cmd) {
        $this->Connection = $Connection;
        $this->index_id = $index_id;
        $this->cols = $cols;
        $this->index_connect_cmd = $index_connect_cmd;
    }

    public function getIndexId() {
        return $this->index_id;
    }

    public function connect() {
        if (!$this->Connection->query($this->index_connect_cmd)) {
            throw new \RuntimeException("Could not query index {$this->index_id}, command: '".join("\t", $this->index_connect_cmd)."'");
        }
    }

    /**
     * @param string $op        HandlerSocket supports '=', '>', '>=', '<', and '<='.
     * @param array  $qargs     index column values to fetch
     * @param array  $extras    array of extra options:
                                limit => array(int limit, int offset)
                                in => array(string in_column, array in_values)
                                filter => array of array(string 'F'|'W', string filter_op, string filter_col, string filter_value)
     */
    public function find($op, array $qargs, array $extras = array()) {
        if (count($qargs) > count($this->cols)) {
            throw new \InvalidArgumentException('count(qargs) must not exceed count(cols)');
        }
        if ($op == '==') {
            $fetch_one = true;
            $op = '=';
        } else {
            $fetch_one = false;
        }
        array_unshift($qargs, $this->getIndexId(), $op, count($qargs));
        if (isset($extras['limit'])) {
            if (is_int($extras['limit']) || (is_string($extras['limit']) && ctype_digit($extras['limit']))) {
                $qargs[] = $extras['limit'];
                $qargs[] = 0;
            } elseif (!is_array($extras['limit']) || count($extras['limit']) != 2) {
                throw new \InvalidArgumentException('extras.limit must be int limit or array(int limit, int offset)');
            } else {
                $qargs[] = reset($extras['limit']);
                $qargs[] = next($extras['limit']);
            }
        }
        if (isset($extras['in'])) {
            if (!is_array($extras['in']) || count($extras['in']) != 2) {
                throw new \InvalidArgumentException('extras.in must be array(string in_column, array in_values)');
            }
            $qargs[] = '@';
            $qargs[] = (string)reset($extras['in']);
            $in_items = (array)next($extras['in']);
            $qargs[] = count($in_items);
            foreach ($in_items as $in_item) {
                $qargs[] = $in_item;
            }
        }
        if (isset($extras['filter'])) {
            if (!is_array($extras['filter'])) {
                throw new \InvalidArgumentException('extras.filter is not an array');
            }
            foreach ($extras['filter'] as $k => $filter) {
                if (!is_array($filter) || count($filter) != 4) {
                    throw new \InvalidArgumentException("extras.filter.$k must be array of 4 items");
                }
                $filter_mode = strtoupper(reset($filter));
                if ($filter_mode != 'F' && $filter_mode != 'W') {
                    throw new \InvalidArgumentException("extras.filter.$k.0 must be F or W");
                }
                $qargs[] = $filter_mode;
                $qargs[] = (string)next($filter); // filter_op
                $qargs[] = (string)next($filter); // filter_col
                $qargs[] = (string)next($filter); // filter_value
            }
        }
        $result = $this->Connection->query($qargs, $this);
        if (!$result) {
            return false;
        }
        $result = $this->parseFindResponse($result);
        if (!empty($result) && $fetch_one) {
            $result = reset($result);
        }
        return $result;
    }

    protected function parseFindResponse(array $response) {
        $num_cols = (int)array_shift($response);
        if (!$num_cols) {
            throw new \LogicException("Malformed find response: num_cols is empty");
        }
        $chunks = array_chunk($response, count($this->cols));
        $keys = $this->cols;
        return array_map(function($values) use($keys) { return array_combine($keys, $values); }, $chunks);
    }

}
