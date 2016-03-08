<?php

class DB {

    public static $queryLog = array();

    public static $tables = array();

    public static function table($name) {
        $tableName = "Table_" . ucfirst($name);
        if (!isset(self::$tables[$tableName])) {
            self::$tables[$tableName] = new $tableName();
        }

        return self::$tables[$tableName];
    }

    public static function flushQueryLog() {
        self::$queryLog = array();
    }

    public static function getQueryLog() {
        return self::$queryLog;
    }

}