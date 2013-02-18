<?php

namespace Carcass\Mysql;

class Client {

    protected
        $Connection,
        $QueryParser = null;

    public function __construct(Connection $Connection, QueryParser $QueryParser = null) {
        $this->setConnection($Connection);
        if (null !== $QueryParser) {
            $this->setQueryParser($QueryParser);
        }
    }

    public function setConnection(Connection $Connection) {
        $this->Connection = $Connection;
        return $this;
    }

    public function setQueryParser(QueryParser $QueryParser) {
        $this->QueryParser = $QueryParser;
        $this->QueryParser->setClient($this);
        return $this;
    }

    public function query($template, array $args = []) {
        $this->executeQueryTemplate($template, $args);
        return $this->getAffectedRows();
    }

    /**
     * Executes a select query. Retrns array of rows, grouped depending on arguments.
     *
     * @code
     * #Example 1:
     *       getAll('select user_id, group_id, f1, f2 from T where user_type={{s(type)}}',
     *              array('type'=>'member'), array('group_id'=>INF, 'user_id'=>INF))
     *
     *       user_id   group_id   f1      f2
     *       -------   --------   -----   -----
     *       1         1          f1_1    f2_1
     *       2         1          f1_2    f2_2
     *       3         2          f1_3    f2_3
     *
     *       array(
     *           #group_id
     *            1 => array(
     *              #user_id
     *               1 => array(array('user_id'=>1, 'group_id'=>1, 'f1'=>'f1_1', 'f2'=>'f2_1')),
     *               2 => array(array('user_id'=>2, 'group_id'=>1, 'f1'=>'f1_2', 'f2'=>'f2_2')),
     *            ),
     *            2 => array(
     *               3 => array(array('user_id'=>3, 'group_id'=>2, 'f1'=>'f1_3', 'f2'=>'f2_3')),
     *            ),
     *        )
     *
     * #Example 2:
     *       getAll('select user_id, group_id, f1, f2 from T where user_type={{s(type)}}',
     *             array('type'=>'member'), array('user_id'=>1))
     *       # returned rows are identical to Example 1
     *       array(
     *           #user_id
     *            1 => array('user_id'=>1, 'group_id'=>1, 'f1'=>'f1_1', 'f2'=>'f2_1'),
     *            2 => array('user_id'=>2, 'group_id'=>1, 'f1'=>'f1_2', 'f2'=>'f2_2'),
     *            3 => array('user_id'=>3, 'group_id'=>2, 'f1'=>'f1_3', 'f2'=>'f2_3'),
     *       )
     *
     * #Example 3:
     *       getAll('select user_id, group_id, f1, f2 from T where user_type={{s(type)}}',
     *             array('type'=>'member'), array('user_id'=>INF))
     *       array(
     *           #user_id
     *            1 => array(array('user_id'=>1, 'group_id'=>1, 'f1'=>'f1_1', 'f2'=>'f2_1')),
     *            ...
     *       )
     * @endcode
     *
     * @param string $tpl template query
     * @param array $params args
     * @param array $keys group keys array of ( group_by_key => 1|INF|field_name ),
     *                             where 1 = values are unique => do not create nested arrays;
     *                                   INF = values are not unique => create nested arrays;
     *                                   string field_name = create scalar value of field_name (must be the last group key)
     * @return array|false
     */
    public function getAll($tpl, array $params = [], array $keys = []) {
        if (false === ($h = $this->executeQueryTemplate($tpl, $params))) {
            return false;
        }
        $result = [];
        $num_keys = count($keys);
        while (false !== ($row = $this->fetch($h))) {
            if (!$num_keys) {
                $result[] = $row;
            } else {
                $r = &$result;
                foreach ($keys as $key_name => $num_of_values) {
                    if (is_string($num_of_values) && '1' !== $num_of_values) {
                        $r = $row[$num_of_values];
                        break;
                    }
                    $r = &$r[$row[$key_name]];
                    if ($num_of_values === INF) {
                        $r = &$r[];
                    }
                }
                $r = $row;
            }
        }
        $this->freeResult($h);
        return $result;
    }

    /**
     * Executes a query and returns the first row
     *
     * @param string $tpl
     * @param array $params
     * @return array|false
     */
    public function getRow($tpl, array $params = []) {
        if (false === ($h = $this->executeQueryTemplate($tpl, $params))) {
            return false;
        }
        $result = $this->fetch($h) ?: [];
        $this->freeResult($h);
        return $result;
    }

    /**
     * Executes a query and returns the first cell of the first row
     *
     * @param string $tpl
     * @param array $params
     * @return string|false|null
     */
    public function getCell($tpl, array $params = [], $field_name_or_offset = 0) {
        $result = $this->getRow($tpl, $params);
        if (!is_array($result)) {
            return false;
        }
        if (is_numeric($field_name_or_offset)) {
            $result = array_values($result);
        }
        return isset($result[$field_name_or_offset]) ? $result[$field_name_or_offset] : null;
    }

    /**
     * Executes a query and returns:
     *
     * $valcol == false -> array of ( $row[$column] )
     *
     * $valcol == true  -> array of ( $row[$column] => $row[$valcol] )
     *
     * @param string $tpl
     * @param array $params
     * @param string|null $column
     * @param string|null $valcol
     * @return array|false
     */
    public function getCol($tpl, array $params = [], $column = null, $valcol = null) {
        if (false === ($h = $this->executeQueryTemplate($tpl, $params))) {
            return false;
        }
        $result = false;
        $row = $this->fetch($h);
        if (!empty($row)) {
            $result = [];
            if (!empty($column)) {
                if (!array_key_exists($column, $row)) {
                    throw new \LogicException('Invalid column key "'.$column.'" in getCol()');
                }
                if (!empty($valcol) && !array_key_exists($valcol, $row)) {
                    throw new \LogicException('Invalid column "'.$valcol.'" in getCol()');
                }
            }
            do {
                if (empty($column)) {
                    $result[] = reset($row);
                } elseif (empty($valcol)) {
                    $result[] = $row[$column];
                } else {
                    $result[$row[$column]] = $row[$valcol];
                }
            } while ($row = $this->fetch($h));
        }
        $this->freeResult($h);
        return $result;
    }

    public function executeQueryTemplate($query_template, array $args = []) {
        $query = $this->parseTemplate($query_template, $args);
        return $this->executeQuery($query);
    }

    protected function parseTemplate($query_template, array $args) {
        return $this->getQueryTemplate($query_template)->parse($args);
    }

    protected function getQueryParser() {
        if (null === $this->QueryParser) {
            $this->QueryParser = $this->assembleDefaultQueryParser();
        }
        return $this->QueryParser;
    }

    protected function assembleDefaultQueryParser() {
        return new QueryParser($this);
    }

    protected function getQueryTemplate($template) {
        return $this->getQueryParser()->getTemplate($template);
    }

    public function __call($method, array $args) {
        if (!method_exists($this->Connection, $method)) {
            throw new \BadMethodCallException('Undefined method: ' . $method);
        }
        return call_user_func_array([$this->Connection, $method], $args);
    }

}
