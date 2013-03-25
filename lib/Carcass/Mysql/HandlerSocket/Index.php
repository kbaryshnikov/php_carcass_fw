<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mysql;

/**
 * Class HandlerSocket_Index
 * @package Carcass\Mysql
 */
class HandlerSocket_Index {

    /**
     * @var HandlerSocket_Connection
     */
    protected $Connection;

    protected
        $index_id,
        $cols,
        $fcols,
        $index_connect_cmd;

    /**
     * @param HandlerSocket_Connection $Connection
     * @param $dbname
     * @param $tablename
     * @param $indexname
     * @param $index_id
     * @param array $cols
     * @param array $fcols
     */
    public function __construct(HandlerSocket_Connection $Connection, $dbname, $tablename, $indexname, $index_id, array $cols, array $fcols = null) {
        $this->Connection = $Connection;
        $this->index_id = $index_id;
        $this->cols = $cols;
        $this->fcols = $fcols;

        $this->index_connect_cmd = ['P', $index_id, $dbname, $tablename, $indexname, join(',', $cols)];
        if ($fcols) {
            $this->index_connect_cmd[] = join(',', $fcols);
        }
    }

    /**
     * @return int
     */
    public function getIndexId() {
        return $this->index_id;
    }

    /**
     * @throws \RuntimeException
     */
    public function connect() {
        if (!$this->Connection->query($this->index_connect_cmd)) {
            throw new \RuntimeException("Could not query index {$this->index_id}, command: '" . join("\t", $this->index_connect_cmd) . "'");
        }
    }

    /**
     * Find and returns the first row. See the find() method phpdoc for details on arguments.
     *
     * @param $op
     * @param array $qargs
     * @param array $extras
     * @return array|null    array row, or null if not found
     */
    public function findOne($op, array $qargs, array $extras = []) {
        return $this->find($op, $qargs, $extras, true);
    }

    /**
     * @param string $op        HandlerSocket supports '=', '>', '>=', '<', and '<='
     * @param array  $qargs     index column values to fetch
     * @param array  $extras    array of extra options:
     *                              limit => array(int limit, int offset), default (1, 0)
     *                              in => array(string in_column, array in_values)
     *                              filter => array of array(string 'F'|'W', string filter_op, string filter_col, string filter_value)
     * @param bool $fetch_one   return only first row. internal flag, external code should use findOne()
     * @throws \InvalidArgumentException
     * @return array
     */
    public function find($op, array $qargs, array $extras = [], $fetch_one = false) {
        if (count($qargs) > count($this->cols)) {
            throw new \InvalidArgumentException('count(qargs) must not exceed count(cols)');
        }
        array_unshift($qargs, $this->getIndexId(), $op, count($qargs));
        // always send limit and offset: filters are not accepted without limit and offset due to handlersocket bug
        $limit = 1;
        $offset = 0;
        if (isset($extras['limit'])) {
            if (is_int($extras['limit']) || (is_string($extras['limit']) && ctype_digit($extras['limit']))) {
                $limit = $extras['limit'];
                $offset = 0;
            } elseif (!is_array($extras['limit']) || count($extras['limit']) != 2) {
                throw new \InvalidArgumentException('extras.limit must be int limit or array(int limit, int offset)');
            } else {
                $limit = reset($extras['limit']);
                $offset = next($extras['limit']);
            }
        }
        $qargs[] = $limit;
        $qargs[] = $offset;
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
                if (!$this->fcols) {
                    throw new \InvalidArgumentException("extras.filter cannot be used: no filter cols specified in index");
                }
                if (!is_array($filter) || count($filter) != 4) {
                    throw new \InvalidArgumentException("extras.filter.$k must be array of 4 items");
                }
                $filter_mode = strtoupper(reset($filter));
                if ($filter_mode != 'F' && $filter_mode != 'W') {
                    throw new \InvalidArgumentException("extras.filter.$k.0 must be F or W");
                }
                $qargs[] = $filter_mode;
                $qargs[] = (string)next($filter); // filter_op

                $col = (string)next($filter);
                if (!ctype_digit($col)) {
                    $filter_col = array_search($col, $this->fcols, true);
                    if (false === $filter_col) {
                        throw new \InvalidArgumentException("extras.filter.$k has unknown column name: '$col'");
                    }
                } else {
                    if ($col >= count($this->fcols)) {
                        throw new \InvalidArgumentException("extras.filter.$k has invalid column offset: '$col'");
                    }
                    $filter_col = $col;
                }
                $qargs[] = $filter_col;

                $qargs[] = (string)next($filter); // filter_value
            }
        }
        $result = $this->Connection->query($qargs, $this);
        return $this->parseFindResponse($result, $fetch_one);
    }

    protected function parseFindResponse($response, $fetch_one = false) {
        if (!$response || !is_array($response)) {
            return $fetch_one ? null : [];
        }
        $num_cols = (int)array_shift($response);
        if (!$num_cols) {
            throw new \LogicException("Malformed find response: num_cols is empty");
        }
        if ($fetch_one) {
            return $response ? array_combine($this->cols, array_slice($response, 0, count($this->cols))) : null;
        }
        $chunks = array_chunk($response, count($this->cols));
        return array_map(
            function ($values)  {
                return array_combine($this->cols, $values);
            }, $chunks
        );
    }

}
