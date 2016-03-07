<?php

class DB {

    public static $sqls = array();

    public static $tables = array();

    public static function table($name) {
        $tableName = "Table_" . ucfirst($name);
        if (!isset(self::$tables[$tableName])) {
            self::$tables[$tableName] = new $tableName();
        }

        return self::$tables[$tableName];
    }

    public static function addSQL($query, $data) {
        if ($data) {
            foreach ($data as $v) {
                $query = str_replace("?", "'" . $v . "'", $query);
            }
        }
        self::$sqls[] = $query;
    }

    public static function getSQL() {
        var_dump(self::$sqls);
    }

}