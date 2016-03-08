<?php

class DB_Table {

    protected $database = "default";

    protected $table;

    private $pdo;

    private $selectExpr = "*";

    private $updateCondition = array();

    private $whereCondition = array();

    private $joinCondition = array();

    private $orderCondition = array();

    private $groupByCondition = "";

    private $havingCondition = "";

    private $limit = 0;

    private $offset = 0;

    private $fetchColumn = false;

    private $enableLogQuery = false;

    private $bindings = array(
            'update' => array(),
            'where' => array(),
            'order' => array()
    );

    protected $options = array(
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    );

    public function __construct() {
        $databaseConfig = Config::get("database" );
        $config = $databaseConfig[$this->database];
        $username = $config['username'];
        $password = $config['password'];
        $charset = isset($config['charset']) ? $config['charset'] : 'utf8';
        $collation = isset($config['collation']) ? $config['collation'] : 'utf8_unicode_ci';
        $prefix = isset($config['prefix']) ? $config['prefix'] : '';

        $dsn = $this->getDsn($config);
        $options = isset($config['option']) ? $config['option'] : array();
        $options = $this->getOptions($options);

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (Exception $e) {
            throw new Exception($e);
        }

        $query = "set names '$charset'" .
                (!is_null($collation) ? " collate '$collation'" : '');
        $this->pdo->prepare($query)->execute();
        $this->table = $prefix . $this->table;

        $enableLogQuery = isset($databaseConfig['enableLogQuery']) ? $databaseConfig['enableLogQuery'] : false;
        if ($enableLogQuery) {
            $this->enableLogQuery();
        }
    }

    private function getDsn($config) {
        extract($config, EXTR_SKIP);
        return isset($port)
                ? "mysql:host={$host};port={$port};dbname={$database}"
                : "mysql:host={$host};dbname={$database}";
    }

    private function getOptions($options) {
        return array_diff_key($this->options, $options) + $options;
    }

    public function avg($column) {
        $this->selectExpr = "AVG($column)";
        $this->fetchColumn = true;
        return $this->get();
    }

    public function min($column) {
        $this->selectExpr = "MIN($column)";
        $this->fetchColumn = true;
        return $this->get();
    }

    public function max($column) {
        $this->selectExpr = "MAX($column)";
        $this->fetchColumn = true;
        return $this->get();
    }

    public function select($column = "*") {
        $this->selectExpr = $column;
        return $this;
    }

    public function where($column, $operator = null, $value = null) {
        if (func_num_args() == 2) {
            list ($value, $operator) = array($operator, "=");
        }

        $this->whereCondition[] = "{$column} $operator ?";
        $this->bindings['where'][] = $value;
        return $this;
    }

    public function value($column) {
        $result = (array) $this->first($column);
        return count($result) > 0 ? reset($result) : null;
    }

    public function count() {
        $this->selectExpr = " COUNT(*) ";
        $this->fetchColumn = true;
        return $this->get();
    }

    public function first() {
        $this->limit = 1;
        $result = $this->get();
        if (isset($result[0])) {
            return $result[0];
        }

        return false;
    }

    public function groupBy($column) {
        $this->groupByCondition = " GROUP BY $column";
    }

    public function having($expr) {
        $this->havingCondition = " HAVING  {$expr}";
    }

    public function delete($id = null) {
        $query = "DELETE from $this->table ";

        if ($id) {
            $this->whereCondition['where'][] = " id = ?";
        }

        $query .= $this->appendWhereCondition();
        $query .= $this->appendOrderCondition();
        $query .= $this->appendLimitCondition();

        return $this->exec($query, $this->bindings);
    }

    public function update($params) {
        $query = "UPDATE $this->table SET ";
        foreach ($params as $key => $value) {
            $this->updateCondition[] = "$key = ?";
            $this->bindings['update'][] = $value;
        }

        $query .= $this->appendUpdateCondition();
        $query .= $this->appendWhereCondition();
        $query .= $this->appendOrderCondition();
        $query .= $this->appendLimitCondition();

        return $this->exec($query, $this->bindings);
    }

    public function join($table, $one, $two) {
        $this->joinCondition[] = " JOIN $table on $one = $two ";
        return $this;
    }

    public function leftJoin($table, $one, $two) {
        $this->joinCondition[] = " LEFT JOIN $table on $one =  $two ";
        return $this;
    }

    public function get() {
        $query = " SELECT {$this->selectExpr} from $this->table ";

        $query .= $this->appendJoinCondition();
        $query .= $this->appendWhereCondition();
        $query .= $this->appendGroupByCondition();
        $query .= $this->appendHavingCondition();
        $query .= $this->appendOrderCondition();
        $query .= $this->appendLimitCondition();

        return $this->exec($query, $this->bindings);
    }

    public function orderBy($column, $order = "desc") {
        $this->orderCondition[] = "$column $order";
        return $this;
    }

    private function appendGroupByCondition() {
        return $this->groupByCondition;
    }

    private function appendHavingCondition() {
        return $this->havingCondition;
    }

    private function appendJoinCondition() {
        if ($this->joinCondition) {
            return implode(" ", $this->joinCondition);
        }
        return "";
    }

    private function appendUpdateCondition() {
        if ($this->whereCondition) {
            return " WHERE " . implode(" AND ", $this->whereCondition);
        }
        return "";
    }

    private function appendLimitCondition() {
        if (($this->limit > 0) && ($this->offset > 0)) {
            return " LIMIT $this->limit OFFSET $this->offset";
        } elseif ($this->limit > 0) {
            return " LIMIT $this->limit ";
        } else {
            return "";
        }
    }

    private function appendOrderCondition() {
        if ($this->orderCondition) {
            return " ORDER BY " . implode(" , ", $this->orderCondition);
        }
    }

    private function appendWhereCondition() {
        if ($this->whereCondition) {
            return " WHERE " . implode(" AND ", $this->whereCondition);
        }
    }

    public function exec($query, $bindings = array()) {
        $result = false;
        $bind = array();
        foreach ($bindings as $v) {
            $bind = array_merge($bind, $v);
        }

        $query = ltrim($query);
        $start_time = microtime(true);
        try {
            $sth = $this->pdo->prepare($query);
            $sth->execute($bind);
        } catch (Exception $e) {
            Logger::error("Query Fail, SQL[".$query."] Error:".$e->getMessage());
            return $result;
        }

        $end_time = microtime(true);
        $executeTime = round(($end_time - $start_time) * 1000);
        if ($this->enableLogQuery) {
            $this->logQuery($query, $bind, $executeTime);
        }

        $type = trim(strtoupper(substr(trim($query), 0, strpos($query, " "))));
        switch ($type) {
            case 'INSERT':
            case 'REPLACE':
                $result = $this->pdo->lastInsertId();
                if (!$result) {
                    return $sth->rowCount();
                }
                break;
            case 'UPDATE':
            case 'DELETE':
                $result = $sth->rowcount();
                break;
            case 'SELECT':
                if ($this->fetchColumn) {
                    $result = $sth->fetchColumn();
                } else {
                    $result = $sth->fetchAll();
                }
                break;
            default:
                break;
        }

        $this->clear();
        return $result;
    }

    private function enableLogQuery() {
        $this->enableLogQuery = true;
    }
    private function logQuery($query, $bindings, $time) {
        DB::$queryLog[] = compact('query', 'bindings', 'time');
    }

    private function clear() {
        $this->fetchColumn = false;
        $this->selectExpr = "*";
        $this->updateCondition = array();
        $this->whereCondition = array();
        $this->joinCondition = array();
        $this->orderCondition = array();
        $this->groupByCondition = "";
        $this->havingCondition = "";
        $this->limit = 0;
        $this->offset = 0;
        $this->bindings = array(
                'update' => array(),
                'where' => array(),
                'order' => array()
        );
    }
}