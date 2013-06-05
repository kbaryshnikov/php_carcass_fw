<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Mysql;

/**
 * MySQL Client
 *
 * Proxies missing methods to $Connection via __call:
 * @method int getAffectedRows()
 * @method int getLastInsertId()
 * @method string escapeString(string $str)
 * @method \Carcass\Connection\Dsn getDsn()
 * @method \Carcass\Mysql\Client selectDatabase(string $db_name)
 * @method string|null getCurrentDatabaseName()
 *
 * @package Carcass\Mysql
 */
class Client {

    /**
     * @var Connection
     */
    protected $Connection;
    /**
     * @var QueryParser
     */
    protected $QueryParser = null;

    /**
     * @param Connection $Connection
     * @param QueryParser $QueryParser
     */
    public function __construct(Connection $Connection, QueryParser $QueryParser = null) {
        $this->setConnection($Connection);
        if (null !== $QueryParser) {
            $this->setQueryParser($QueryParser);
        }
    }

    /**
     * @param Connection $Connection
     * @return $this
     */
    public function setConnection(Connection $Connection) {
        $this->Connection = $Connection;
        return $this;
    }

    /**
     * @param QueryParser $QueryParser
     * @return $this
     */
    public function setQueryParser(QueryParser $QueryParser) {
        $this->QueryParser = $QueryParser;
        $this->QueryParser->setClient($this);
        return $this;
    }

    /**
     * @param string $template
     * @param array $args
     * @return int affected rows
     */
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
     * @return array|null
     */
    public function getAll($tpl, array $params = [], array $keys = []) {
        if (null === ($h = $this->executeQueryTemplate($tpl, $params))) {
            return null;
        }
        $result = [];
        $num_keys = count($keys);
        while (null !== ($row = $this->Connection->fetch($h))) {
            if (!$num_keys) {
                $result[] = $row;
            } else {
                $r = &$result;
                foreach ($keys as $key_name => $num_of_values) {
                    if (is_string($num_of_values) && '1' !== $num_of_values) {
                        $r[$row[$key_name]] = $row[$num_of_values];
                        continue 2;
                    }
                    $r = &$r[$row[$key_name]];
                    if ($num_of_values === INF) {
                        $r = &$r[];
                    }
                }
                $r = $row;
            }
        }
        $this->Connection->freeResult($h);
        return $result;
    }

    /**
     * Executes a query and returns the first row
     *
     * @param string $tpl
     * @param array $params
     * @return array|null
     */
    public function getRow($tpl, array $params = []) {
        if (null === ($h = $this->executeQueryTemplate($tpl, $params))) {
            return null;
        }
        $result = $this->Connection->fetch($h) ?: [];
        $this->Connection->freeResult($h);
        return $result;
    }

    /**
     * Executes a query and returns the first cell of the first row
     *
     * @param string $tpl
     * @param array $params
     * @param int|string $field_name_or_offset
     * @return string|null
     */
    public function getCell($tpl, array $params = [], $field_name_or_offset = 0) {
        $result = $this->getRow($tpl, $params);
        if (!is_array($result)) {
            return null;
        }
        if (is_numeric($field_name_or_offset)) {
            $result = array_values($result);
        }
        return isset($result[$field_name_or_offset]) ? $result[$field_name_or_offset] : null;
    }

    /**
     * Executes a query and returns:
     *
     * $valcol is null  -> array of ( $row[$column] )
     * else             -> array of ( $row[$column] => $row[$valcol] )
     *
     * @param string $tpl
     * @param array $params
     * @param string|null $column name, or, if null, first column
     * @param string|null $valcol
     * @throws \LogicException
     * @return array|null
     */
    public function getCol($tpl, array $params = [], $column = null, $valcol = null) {
        if (null === ($h = $this->executeQueryTemplate($tpl, $params))) {
            return null;
        }
        $result = [];
        $row = $this->Connection->fetch($h);
        if ($row) {
            if (null !== $column) {
                if (!array_key_exists($column, $row)) {
                    throw new \LogicException('Invalid column key "'.$column.'" in getCol()');
                }
                if (null !== $valcol && !array_key_exists($valcol, $row)) {
                    throw new \LogicException('Invalid column "'.$valcol.'" in getCol()');
                }
            }
            do {
                if (null === $column) {
                    $result[] = reset($row);
                } elseif (null === $valcol) {
                    $result[] = $row[$column];
                } else {
                    $result[$row[$column]] = $row[$valcol];
                }
            } while (null !== $row = $this->Connection->fetch($h));
        }
        $this->Connection->freeResult($h);
        return $result;
    }

    /**
     * @param string $query_template
     * @param array $args
     * @return mixed
     */
    public function executeQueryTemplate($query_template, array $args = []) {
        $query = $this->parseTemplate($query_template, $args);
        return $this->Connection->executeQuery($query);
    }

    /**
     * @param string $query_template
     * @param array $args
     * @return string
     */
    protected function parseTemplate($query_template, array $args) {
        return $this->getQueryTemplate($query_template)->parse($args);
    }

    /**
     * @return QueryParser
     */
    protected function getQueryParser() {
        if (null === $this->QueryParser) {
            $this->QueryParser = $this->assembleDefaultQueryParser();
        }
        return $this->QueryParser;
    }

    /**
     * @return QueryParser
     */
    protected function assembleDefaultQueryParser() {
        return new QueryParser($this);
    }

    /**
     * @param string $template
     * @return QueryTemplate
     */
    protected function getQueryTemplate($template) {
        return $this->getQueryParser()->getTemplate($template);
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, array $args) {
        if (!method_exists($this->Connection, $method)) {
            throw new \BadMethodCallException('Undefined method: ' . $method);
        }
        $result = call_user_func_array([$this->Connection, $method], $args);
        if ($result === $this->Connection) {
            $result = $this;
        }
        return $result;
    }

}
